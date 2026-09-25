<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        protected StockService $stockService,
        protected CashFlowService $cashFlowService,
        protected DocumentNumberService $numberService,
        protected ExpenseService $expenseService,
    ) {}

    /**
     * Buat SO lengkap dengan item-itemnya, otomatis alokasikan stok
     * secara FIFO per item dan hitung HPP riil.
     *
     * @param array $data ['customer_id', 'so_date', 'note', 'source_id', 'estimated_packing_cost'] — 'so_number' opsional,
     *                     kalau tidak diisi akan digenerate otomatis: SO/{Bulan Romawi}/{Tahun}/{Urut}
     * @param array $items [['product_id', 'qty'], ...] — TANPA sell_price per baris. Harga jual
     *                      diinput 1 angka total lewat $totalAmount (lihat parameter di bawah),
     *                      lalu dibagi ke tiap baris lewat distributeSellPrice().
     * @param float $totalAmount Total harga jual seluruh transaksi (1 angka, bukan per unit/baris) —
     *                            dipakai apa adanya sebagai total_amount SO, TIDAK dihitung dari
     *                            qty x harga per item, karena harga jual marketplace biasanya sudah
     *                            berupa angka total setelah potongan pajak/komisi.
     * @param array $otherCosts [['expense_category_id', 'amount', 'description'], ...] — biaya
     *                          tambahan (packing, ongkir, dll), opsional & boleh lebih dari satu.
     *                          Tiap baris otomatis jadi record Expense terpisah yang terhubung ke
     *                          SO ini, TAPI tidak menambah total_amount/paid_amount SO.
     */
    public function create(array $data, array $items, float $totalAmount, ?float $initialPayment = null, string $paymentMethod = 'cash', array $otherCosts = []): SalesOrder
    {
        return DB::transaction(function () use ($data, $items, $totalAmount, $initialPayment, $paymentMethod, $otherCosts) {
            $data['so_number'] ??= $this->numberService->generate('SO', SalesOrder::class, 'so_number');

            $so = SalesOrder::create([
                ...$data,
                'total_amount'   => $totalAmount,
                'total_hpp'      => 0, // diisi setelah alokasi FIFO tiap item
                'paid_amount'    => 0,
                'payment_status' => 'unpaid',
            ]);

            $saleItems = [];
            $totalHpp  = 0;

            // Tahap 1: buat baris item & alokasikan stok FIFO dulu, supaya HPP riil
            // tiap baris diketahui. sell_price/subtotal per baris sengaja diisi 0
            // dulu — baru dihitung di tahap 2 setelah semua HPP kekumpul.
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                $saleItem = $so->items()->create([
                    'product_id'   => $product->id,
                    'qty'          => $item['qty'],
                    'sell_price'   => 0,
                    'subtotal'     => 0,
                    'hpp_subtotal' => 0,
                ]);

                // Inti FIFO: potong stok dari batch tertua, dapatkan rincian alokasi
                $allocations = $this->stockService->allocateFifo(
                    $product,
                    $item['qty'],
                    $so->so_date,
                    $saleItem
                );

                $itemHpp = 0;
                foreach ($allocations as $alloc) {
                    $saleItem->allocations()->create($alloc);
                    $itemHpp += $alloc['hpp_subtotal'];
                }

                $saleItem->update(['hpp_subtotal' => $itemHpp]);
                $totalHpp += $itemHpp;

                $saleItems[] = $saleItem;
            }

            // Tahap 2: bagi total harga jual ke tiap baris, proporsional terhadap HPP.
            $this->distributeSellPrice($saleItems, $totalAmount, $totalHpp);

            $so->update(['total_hpp' => $totalHpp]);

            $this->syncOtherCosts($so, $otherCosts);

            if ($initialPayment && $initialPayment > 0) {
                $this->addPayment($so, $so->so_date, $initialPayment, $paymentMethod, 'Pembayaran awal saat transaksi');
            }

            return $so->fresh(['items.allocations', 'payments', 'otherCosts']);
        });
    }

    /**
     * Bagi 1 angka total harga jual (input manual untuk seluruh transaksi,
     * bukan per unit/baris) ke tiap baris SaleItem, proporsional terhadap
     * HPP baris tersebut — barang yang modalnya lebih besar otomatis
     * "menanggung" porsi harga jual yang lebih besar juga. Ini yang membuat
     * subtotal & margin per barang tetap bisa dihitung meskipun user cuma
     * input 1 harga total (kasus umum: jualan marketplace yang harganya
     * sudah dipotong pajak/komisi, jadi susah ditelusuri harga per unit
     * aslinya). Berlaku untuk berapa pun baris item & produk yang berbeda-beda.
     *
     * Kalau total HPP seluruh baris = 0 (mis. semua barang bermodal Rp0),
     * fallback dibagi rata berdasarkan proporsi qty supaya tidak divide-by-zero.
     *
     * Pembulatan: baris TERAKHIR menampung sisa hasil pembulatan baris-baris
     * sebelumnya, supaya jumlah seluruh subtotal baris persis sama dengan
     * $totalAmount (tidak melenceng walau 1 rupiah pun).
     *
     * @param \App\Models\SaleItem[] $saleItems
     */
    protected function distributeSellPrice(array $saleItems, float $totalAmount, float $totalHpp): void
    {
        $count = count($saleItems);
        if ($count === 0) {
            return;
        }

        $totalQty  = array_sum(array_map(fn ($i) => (int) $i->qty, $saleItems));
        $allocated = 0.0;

        foreach ($saleItems as $index => $saleItem) {
            $isLast = $index === $count - 1;

            if ($isLast) {
                $subtotal = $totalAmount - $allocated;
            } elseif ($totalHpp > 0) {
                $subtotal = round($totalAmount * ((float) $saleItem->hpp_subtotal / $totalHpp), 2);
            } elseif ($totalQty > 0) {
                $subtotal = round($totalAmount * ($saleItem->qty / $totalQty), 2);
            } else {
                $subtotal = 0;
            }

            $allocated += $subtotal;

            $saleItem->update([
                'subtotal'   => $subtotal,
                'sell_price' => $saleItem->qty > 0 ? round($subtotal / $saleItem->qty, 2) : 0,
            ]);
        }
    }

    /**
     * Ganti seluruh biaya lainnya (Expense) milik SO ini dengan set yang baru.
     * Dipakai saat create (dari kosong) maupun update (replace total). Expense
     * lama dihapus lewat ExpenseService::delete() supaya cash_flow terkait ikut
     * dibersihkan, baru dibuat expense baru lewat ExpenseService::create() supaya
     * cash_flow baru ikut tercatat — konsisten dengan cara item PO/SO di-replace.
     *
     * @param array $otherCosts [['expense_category_id', 'amount', 'description'], ...]
     */
    protected function syncOtherCosts(SalesOrder $so, array $otherCosts): void
    {
        foreach ($so->otherCosts()->get() as $oldExpense) {
            $this->expenseService->delete($oldExpense);
        }

        foreach ($otherCosts as $cost) {
            $this->expenseService->create([
                'expense_category_id' => $cost['expense_category_id'],
                'sales_order_id'      => $so->id,
                'expense_date'        => $so->so_date,
                'amount'              => $cost['amount'],
                'description'         => $cost['description'] ?? "Biaya tambahan SO #{$so->so_number}",
            ]);
        }
    }

    public function addPayment(SalesOrder $so, string $date, float $amount, string $method = 'cash', ?string $note = null): SalesPayment
    {
        return DB::transaction(function () use ($so, $date, $amount, $method, $note) {
            $remaining = $so->total_amount - $so->paid_amount;

            if ($amount > $remaining) {
                throw new \RuntimeException(
                    "Jumlah pembayaran (Rp {$amount}) melebihi sisa piutang (Rp {$remaining}) untuk SO #{$so->so_number}."
                );
            }

            $payment = $so->payments()->create([
                'payment_date' => $date,
                'amount'       => $amount,
                'method'       => $method,
                'note'         => $note,
            ]);

            $so->paid_amount += $amount;
            $so->payment_status = $this->resolvePaymentStatus($so->total_amount, $so->paid_amount);
            $so->save();

            $this->cashFlowService->recordIn(
                $date,
                $amount,
                $payment,
                "Pembayaran SO #{$so->so_number} dari " . ($so->customer->name ?? '-')
            );

            return $payment;
        });
    }

    /**
     * Edit pembayaran yang sudah tercatat (koreksi salah input nominal/tanggal/dll).
     * paid_amount & payment_status SO dihitung ulang otomatis, dan entry cash_flow
     * terkait ikut disinkronkan — semua dalam satu transaksi supaya konsisten.
     *
     * @param array $data ['payment_date', 'amount', 'method', 'note']
     *
     * @throws \RuntimeException kalau nominal baru bikin total pembayaran melebihi total SO
     */
    public function updatePayment(SalesPayment $payment, array $data): SalesPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $so = $payment->salesOrder()->lockForUpdate()->first();

            $oldAmount = (float) $payment->amount;
            $newAmount = (float) $data['amount'];
            $newPaidTotal = (float) $so->paid_amount - $oldAmount + $newAmount;

            if ($newPaidTotal > (float) $so->total_amount) {
                $maxAllowed = $oldAmount + ((float) $so->total_amount - (float) $so->paid_amount);
                throw new \RuntimeException(
                    "Nominal baru (Rp {$newAmount}) membuat total pembayaran melebihi total SO. Maksimal untuk pembayaran ini: Rp {$maxAllowed}."
                );
            }

            $payment->update([
                'payment_date' => $data['payment_date'],
                'amount'       => $newAmount,
                'method'       => $data['method'],
                'note'         => $data['note'] ?? null,
            ]);

            $so->paid_amount = $newPaidTotal;
            $so->payment_status = $this->resolvePaymentStatus($so->total_amount, $newPaidTotal);
            $so->save();

            $this->cashFlowService->updateForSource($payment, $data['payment_date'], $newAmount);

            return $payment->fresh();
        });
    }

    /**
     * Hapus pembayaran yang sudah tercatat. paid_amount & payment_status SO
     * dihitung ulang, dan entry cash_flow terkait ikut dihapus.
     */
    public function deletePayment(SalesPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $so = $payment->salesOrder()->lockForUpdate()->first();

            $this->cashFlowService->deleteForSource($payment);

            $so->paid_amount = (float) $so->paid_amount - (float) $payment->amount;
            $so->payment_status = $this->resolvePaymentStatus($so->total_amount, $so->paid_amount);
            $so->save();

            $payment->delete();
        });
    }

    /**
     * Update SO yang sudah ada: ganti data utama + replace semua item lama
     * dengan item baru. Alokasi FIFO lama dikembalikan ke batch asal dulu,
     * baru item baru dialokasikan ulang. Tetap boleh dipanggil meskipun SO
     * sudah ada pembayaran (partial/lunas) — yang HANYA memblokir adalah
     * kalau ada item dari SO ini yang sudah terlanjur diretur customer.
     *
     * Karena total SO bisa berubah sementara paid_amount tidak (pembayaran
     * lama tidak ikut diubah di sini), payment_status dihitung ulang dari
     * total baru vs paid_amount yang sudah ada. Kalau total baru justru
     * lebih kecil dari yang sudah dibayar, ditolak — user harus koreksi/
     * hapus pembayaran dulu supaya tidak terjadi kondisi "kelebihan bayar".
     *
     * @param array $data ['customer_id', 'so_date', 'note', 'source_id', 'estimated_packing_cost']
     * @param array $items [['product_id', 'qty'], ...] — TANPA sell_price per baris, sama seperti create().
     * @param float $totalAmount Total harga jual baru untuk seluruh transaksi (1 angka), dipakai
     *                            apa adanya, lalu dibagi ke tiap baris lewat distributeSellPrice().
     * @param array $otherCosts [['expense_category_id', 'amount', 'description'], ...] — akan
     *                          MENGGANTI seluruh biaya lainnya lama milik SO ini (lihat syncOtherCosts).
     *
     * @throws \RuntimeException kalau ada item yang sudah diretur, stok tidak
     *                            cukup untuk item baru, atau total baru lebih
     *                            kecil dari paid_amount
     */
    public function update(SalesOrder $so, array $data, array $items, float $totalAmount, array $otherCosts = []): SalesOrder
    {
        return DB::transaction(function () use ($so, $data, $items, $totalAmount, $otherCosts) {
            $so->loadMissing('items.allocations');

            $this->guardCanModify($so);

            if ($totalAmount < (float) $so->paid_amount) {
                throw new \RuntimeException(
                    "Total transaksi baru (Rp {$totalAmount}) lebih kecil dari total yang sudah dibayar (Rp {$so->paid_amount}) untuk SO #{$so->so_number}. Koreksi/kurangi pembayaran dulu sebelum mengubah item transaksi ini."
                );
            }

            foreach ($so->items as $saleItem) {
                $this->stockService->reverseAllocations(
                    $saleItem->allocations,
                    $so->so_date,
                    "Edit transaksi SO #{$so->so_number} — kembalikan alokasi lama"
                );
                $saleItem->delete(); // allocations ikut terhapus (cascadeOnDelete)
            }

            $so->update([
                'customer_id'    => $data['customer_id'],
                'so_date'        => $data['so_date'],
                'note'           => $data['note'] ?? null,
                'source_id'      => $data['source_id'] ?? $so->source_id,
                'estimated_packing_cost' => $data['estimated_packing_cost'] ?? $so->estimated_packing_cost,
                'total_amount'   => $totalAmount,
                'total_hpp'      => 0,
                'payment_status' => $this->resolvePaymentStatus($totalAmount, (float) $so->paid_amount),
            ]);

            $saleItems = [];
            $totalHpp  = 0;

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                $saleItem = $so->items()->create([
                    'product_id'   => $product->id,
                    'qty'          => $item['qty'],
                    'sell_price'   => 0,
                    'subtotal'     => 0,
                    'hpp_subtotal' => 0,
                ]);

                $allocations = $this->stockService->allocateFifo(
                    $product,
                    $item['qty'],
                    $so->so_date,
                    $saleItem
                );

                $itemHpp = 0;
                foreach ($allocations as $alloc) {
                    $saleItem->allocations()->create($alloc);
                    $itemHpp += $alloc['hpp_subtotal'];
                }

                $saleItem->update(['hpp_subtotal' => $itemHpp]);
                $totalHpp += $itemHpp;

                $saleItems[] = $saleItem;
            }

            $this->distributeSellPrice($saleItems, $totalAmount, $totalHpp);

            $so->update(['total_hpp' => $totalHpp]);

            $this->syncOtherCosts($so, $otherCosts);

            return $so->fresh(['items.allocations', 'payments', 'otherCosts']);
        });
    }

    /**
     * Hapus SO: kembalikan semua alokasi FIFO ke batch asal, hapus entry
     * cashflow dari semua pembayaran yang sudah tercatat, lalu hapus SO
     * (item & pembayaran ikut terhapus lewat cascade). Tetap boleh dipanggil
     * meskipun SO sudah ada pembayaran (partial/lunas) — yang HANYA
     * memblokir adalah kalau ada item dari SO ini yang sudah terlanjur
     * diretur customer.
     *
     * @throws \RuntimeException kalau ada item yang sudah diretur
     */
    public function delete(SalesOrder $so): void
    {
        DB::transaction(function () use ($so) {
            $so->loadMissing('items.allocations', 'payments', 'otherCosts');

            $this->guardCanModify($so);

            foreach ($so->items as $saleItem) {
                $this->stockService->reverseAllocations(
                    $saleItem->allocations,
                    $so->so_date,
                    "Pembatalan transaksi SO #{$so->so_number}"
                );
            }

            foreach ($so->payments as $payment) {
                $this->cashFlowService->deleteForSource($payment);
            }

            // Biaya lainnya (Expense) turunan SO ini dihapus lewat ExpenseService
            // supaya cash_flow terkait ikut dibersihkan (bukan lewat cascadeOnDelete
            // DB saja, karena itu tidak akan menyentuh tabel cash_flows).
            foreach ($so->otherCosts as $expense) {
                $this->expenseService->delete($expense);
            }

            // items & payments ikut terhapus otomatis (cascadeOnDelete di migration)
            $so->delete();
        });
    }

    /**
     * Pastikan SO boleh diedit/dihapus. Status pembayaran TIDAK lagi jadi
     * penghalang (lihat SalesOrder::canBeModified) — satu-satunya syarat
     * adalah belum ada item dari SO ini yang diretur customer, karena kalau
     * sudah diretur, mengubah/menghapus SO akan merusak catatan retur yang
     * sudah terlanjur jalan (retur mengacu ke baris item & alokasi FIFO
     * milik SO ini).
     */
    protected function guardCanModify(SalesOrder $so): void
    {
        foreach ($so->items as $saleItem) {
            if ($saleItem->qty_returned > 0) {
                throw new \RuntimeException(
                    "Transaksi #{$so->so_number} tidak bisa diedit/dihapus karena barang \"{$saleItem->product->name}\" dari transaksi ini sudah pernah diretur customer."
                );
            }
        }
    }

    protected function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        return $paid >= $total ? 'paid' : 'partial';
    }
}