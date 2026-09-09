<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseReturnService
{
    public function __construct(
        protected StockService $stockService,
        protected CashFlowService $cashFlowService,
        protected DocumentNumberService $numberService,
    ) {}

    /**
     * Buat retur pembelian (barang dikembalikan ke supplier) untuk 1 atau lebih
     * baris item dari sebuah PO. Stok dikurangi dari batch asal masing-masing
     * item (hanya boleh sebesar sisa yang belum terjual), dan total_amount PO
     * dikurangi senilai retur (hutang ke supplier berkurang).
     *
     * Kalau paid_amount PO ternyata sudah melebihi total_amount yang baru
     * (PO sudah kadung dibayar lebih dari sisa hutang setelah dipotong retur),
     * selisihnya dicatat sebagai kas masuk (dana refund dari supplier).
     *
     * @param array $data ['return_date', 'note']
     * @param array $items [['purchase_order_item_id', 'qty'], ...]
     *
     * @throws RuntimeException kalau item bukan milik PO ini, atau qty retur melebihi sisa stok
     */
    public function create(PurchaseOrder $po, array $data, array $items): PurchaseReturn
    {
        return DB::transaction(function () use ($po, $data, $items) {
            $po = PurchaseOrder::where('id', $po->id)->lockForUpdate()->first();

            if (empty($items)) {
                throw new RuntimeException('Minimal harus ada 1 item barang yang diretur.');
            }

            $totalAmount = 0;
            $lines = [];

            foreach ($items as $line) {
                $qty = (int) $line['qty'];

                if ($qty <= 0) {
                    continue;
                }

                $poItem = PurchaseOrderItem::where('id', $line['purchase_order_item_id'])
                    ->where('purchase_order_id', $po->id)
                    ->first();

                if (! $poItem) {
                    throw new RuntimeException('Item barang yang diretur tidak ditemukan pada PO ini.');
                }

                $subtotal = $qty * (float) $poItem->buy_price;
                $totalAmount += $subtotal;

                $lines[] = [
                    'item'     => $poItem,
                    'qty'      => $qty,
                    'subtotal' => $subtotal,
                ];
            }

            if (empty($lines)) {
                throw new RuntimeException('Minimal harus ada 1 item barang yang diretur.');
            }

            $returnNumber = $data['return_number'] ?? $this->numberService->generate('RTR', PurchaseReturn::class, 'return_number');

            $return = PurchaseReturn::create([
                'return_number'      => $returnNumber,
                'purchase_order_id'  => $po->id,
                'return_date'        => $data['return_date'],
                'total_amount'       => $totalAmount,
                'note'               => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $line['item'];

                $returnItem = $return->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id'             => $poItem->product_id,
                    'qty'                    => $line['qty'],
                    'buy_price'              => $poItem->buy_price,
                    'subtotal'               => $line['subtotal'],
                ]);

                // Ini yang melempar RuntimeException kalau qty retur > sisa stok
                // dari batch PO item tsb (barang sudah kadung terjual).
                $this->stockService->returnToSupplier($poItem, $line['qty'], $data['return_date'], $returnItem);
            }

            // Retur mengurangi total hutang PO — total_amount tidak pernah negatif.
            $po->total_amount = max((float) $po->total_amount - $totalAmount, 0);
            $po->payment_status = $this->resolvePaymentStatus($po->total_amount, $po->paid_amount);
            $po->save();

            // Kalau PO sudah kadung dibayar lebih dari sisa hutang setelah retur,
            // kelebihannya adalah dana yang seharusnya dikembalikan supplier — dicatat
            // sebagai kas masuk (tidak mengubah paid_amount, itu tetap jejak historis
            // uang yang benar-benar sudah dibayarkan).
            $overpaid = (float) $po->paid_amount - (float) $po->total_amount;
            if ($overpaid > 0) {
                $this->cashFlowService->recordIn(
                    $data['return_date'],
                    $overpaid,
                    $return,
                    "Refund retur {$return->return_number} — PO #{$po->po_number} ke {$po->supplier->name}"
                );
            }

            return $return->fresh(['items.product', 'purchaseOrder']);
        });
    }

    /**
     * Hapus/batalkan retur: kembalikan qty ke batch stok asal, kembalikan
     * total_amount PO (tambah balik senilai retur), dan hapus entry kas masuk
     * (refund) yang tercatat waktu retur ini dibuat kalau ada — supaya arus
     * kas ikut konsisten, tidak nyisa entry refund untuk retur yang sudah batal.
     */
    public function delete(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->loadMissing('items.purchaseOrderItem.purchaseOrder', 'items.product');

            $po = PurchaseOrder::where('id', $return->purchase_order_id)->lockForUpdate()->first();

            $today = now()->toDateString();

            foreach ($return->items as $returnItem) {
                $returnItem->setRelation('purchaseReturn', $return);
                $this->stockService->undoReturnToSupplier($returnItem, $today);
            }

            // Kembalikan total_amount PO senilai retur ini (hutang naik lagi).
            $po->total_amount = (float) $po->total_amount + (float) $return->total_amount;
            $po->payment_status = $this->resolvePaymentStatus($po->total_amount, $po->paid_amount);
            $po->save();

            // Hapus entry kas masuk (refund) yang tercatat waktu retur ini dibuat
            // (kalau dulu PO sempat overpaid setelah retur ini). Kalau tidak ada
            // entry (retur dulu tidak memicu refund), ini no-op.
            CashFlow::where('source_type', PurchaseReturn::class)
                ->where('source_id', $return->id)
                ->delete();

            // Item ikut terhapus otomatis (cascadeOnDelete di migration purchase_return_items).
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
