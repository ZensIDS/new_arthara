<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\SaleItem;
use App\Models\SaleReturnItemAllocation;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesReturnService
{
    public function __construct(
        protected StockService $stockService,
        protected CashFlowService $cashFlowService,
        protected DocumentNumberService $numberService,
    ) {}

    /**
     * Buat retur penjualan (barang dikembalikan oleh customer) untuk 1 atau lebih
     * baris item dari sebuah SO. Qty dikembalikan ke batch-batch asal tempat item
     * itu dulu dialokasikan (FIFO, mengikuti urutan alokasi asli — sale_item bisa
     * dulu dipenuhi dari lebih dari 1 batch), dan total_amount + total_hpp SO
     * dikurangi senilai retur.
     *
     * Kalau paid_amount SO ternyata sudah melebihi total_amount yang baru
     * (customer sudah kadung bayar lebih dari sisa piutang setelah dipotong
     * retur), selisihnya dicatat sebagai kas KELUAR (refund ke customer).
     *
     * @param array $data ['return_date', 'note']
     * @param array $items [['sale_item_id', 'qty'], ...]
     *
     * @throws RuntimeException kalau item bukan milik SO ini, atau qty retur melebihi qty yang belum diretur
     */
    public function create(SalesOrder $so, array $data, array $items): SalesReturn
    {
        return DB::transaction(function () use ($so, $data, $items) {
            $so = SalesOrder::where('id', $so->id)->lockForUpdate()->first();

            if (empty($items)) {
                throw new RuntimeException('Minimal harus ada 1 item barang yang diretur.');
            }

            $returnNumber = $data['return_number'] ?? $this->numberService->generate('RTJ', SalesReturn::class, 'return_number');

            $return = SalesReturn::create([
                'return_number'   => $returnNumber,
                'sales_order_id'  => $so->id,
                'return_date'     => $data['return_date'],
                'total_amount'    => 0,
                'total_hpp'       => 0,
                'note'            => $data['note'] ?? null,
            ]);

            $totalAmount = 0;
            $totalHpp = 0;

            foreach ($items as $line) {
                $qty = (int) $line['qty'];

                if ($qty <= 0) {
                    continue;
                }

                $saleItem = SaleItem::where('id', $line['sale_item_id'])
                    ->where('sales_order_id', $so->id)
                    ->first();

                if (! $saleItem) {
                    throw new RuntimeException('Item barang yang diretur tidak ditemukan pada transaksi ini.');
                }

                $alreadyReturned = (int) $saleItem->returnItems()->sum('qty');
                $maxReturnable = $saleItem->qty - $alreadyReturned;

                if ($qty > $maxReturnable) {
                    throw new RuntimeException(
                        "Qty retur untuk \"{$saleItem->product->name}\" ({$qty}) melebihi qty yang bisa diretur ({$maxReturnable})."
                    );
                }

                $subtotal = $qty * (float) $saleItem->sell_price;

                $returnItem = $return->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id'   => $saleItem->product_id,
                    'qty'          => $qty,
                    'sell_price'   => $saleItem->sell_price,
                    'subtotal'     => $subtotal,
                    'hpp_subtotal' => 0, // diisi setelah dipecah per batch di bawah
                ]);

                // Kembalikan qty ke batch asal, urut sesuai urutan alokasi
                // aslinya (FIFO), sebesar yang masih tersisa untuk diretur per
                // alokasi (qty_taken - qty yang sudah pernah diretur dari alokasi itu).
                $remainingToReturn = $qty;
                $itemHpp = 0;

                foreach ($saleItem->allocations as $alloc) {
                    if ($remainingToReturn <= 0) {
                        break;
                    }

                    $returnedFromAlloc = (int) SaleReturnItemAllocation::where('sale_item_allocation_id', $alloc->id)->sum('qty');
                    $availableInAlloc = $alloc->qty_taken - $returnedFromAlloc;

                    if ($availableInAlloc <= 0) {
                        continue;
                    }

                    $qtyFromAlloc = min($availableInAlloc, $remainingToReturn);
                    $hppFromAlloc = $qtyFromAlloc * (float) $alloc->buy_price_at_time;

                    $returnAlloc = $returnItem->allocations()->create([
                        'sale_item_allocation_id' => $alloc->id,
                        'stock_batch_id'          => $alloc->stock_batch_id,
                        'qty'                     => $qtyFromAlloc,
                        'buy_price_at_time'       => $alloc->buy_price_at_time,
                        'hpp_subtotal'            => $hppFromAlloc,
                    ]);

                    $this->stockService->returnFromCustomer($alloc, $qtyFromAlloc, $data['return_date'], $returnAlloc);

                    $itemHpp += $hppFromAlloc;
                    $remainingToReturn -= $qtyFromAlloc;
                }

                if ($remainingToReturn > 0) {
                    // Seharusnya tidak pernah terjadi kalau $maxReturnable dihitung benar
                    // di atas — safety net kalau ada inkonsistensi data alokasi.
                    throw new RuntimeException(
                        "Qty retur untuk \"{$saleItem->product->name}\" melebihi qty yang benar-benar tercatat pernah dijual dari batch manapun."
                    );
                }

                $returnItem->update(['hpp_subtotal' => $itemHpp]);

                $totalAmount += $subtotal;
                $totalHpp += $itemHpp;
            }

            $return->update([
                'total_amount' => $totalAmount,
                'total_hpp'    => $totalHpp,
            ]);

            // Retur mengurangi total penjualan & HPP SO — tidak pernah negatif.
            $so->total_amount = max((float) $so->total_amount - $totalAmount, 0);
            $so->total_hpp = max((float) $so->total_hpp - $totalHpp, 0);
            $so->payment_status = $this->resolvePaymentStatus($so->total_amount, $so->paid_amount);
            $so->save();

            // Kalau customer sudah kadung bayar lebih dari sisa piutang setelah
            // retur, kelebihannya adalah dana yang harus dikembalikan ke customer
            // — dicatat sebagai kas KELUAR (tidak mengubah paid_amount, itu tetap
            // jejak historis uang yang benar-benar sudah diterima).
            $overpaid = (float) $so->paid_amount - (float) $so->total_amount;
            if ($overpaid > 0) {
                $this->cashFlowService->recordOut(
                    $data['return_date'],
                    $overpaid,
                    $return,
                    "Refund retur {$return->return_number} — SO #{$so->so_number} ke " . ($so->customer->name ?? 'Customer umum')
                );
            }

            return $return->fresh(['items.product', 'items.allocations', 'salesOrder']);
        });
    }

    /**
     * Hapus/batalkan retur: keluarkan lagi qty dari batch stok, kembalikan
     * total_amount & total_hpp SO (tambah balik senilai retur), dan hapus entry
     * kas keluar (refund) yang tercatat waktu retur ini dibuat kalau ada.
     *
     * @throws RuntimeException kalau ada barang hasil retur ini yang sudah kadung
     *                          terjual lagi/diretur ke supplier (lihat StockService::undoReturnFromCustomer())
     */
    public function delete(SalesReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->loadMissing('items.allocations.saleReturnItem.product');

            $so = SalesOrder::where('id', $return->sales_order_id)->lockForUpdate()->first();

            $today = now()->toDateString();

            foreach ($return->items as $returnItem) {
                foreach ($returnItem->allocations as $returnAlloc) {
                    $returnAlloc->setRelation('saleReturnItem', $returnItem);
                    $this->stockService->undoReturnFromCustomer($returnAlloc, $today);
                }
            }

            // Kembalikan total_amount & total_hpp SO senilai retur ini.
            $so->total_amount = (float) $so->total_amount + (float) $return->total_amount;
            $so->total_hpp = (float) $so->total_hpp + (float) $return->total_hpp;
            $so->payment_status = $this->resolvePaymentStatus($so->total_amount, $so->paid_amount);
            $so->save();

            // Hapus entry kas keluar (refund) yang tercatat waktu retur ini dibuat
            // (kalau dulu SO sempat overpaid setelah retur ini). Kalau tidak ada
            // entry (retur dulu tidak memicu refund), ini no-op.
            CashFlow::where('source_type', SalesReturn::class)
                ->where('source_id', $return->id)
                ->delete();

            // Item & alokasi retur ikut terhapus otomatis (cascadeOnDelete).
            $return->delete();
        });
    }

    protected function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($total <= 0) {
            return 'paid';
        }

        if ($paid <= 0) {
            return 'unpaid';
        }

        return $paid >= $total ? 'paid' : 'partial';
    }
}
