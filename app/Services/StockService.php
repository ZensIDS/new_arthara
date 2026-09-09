<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturnItem;
use App\Models\SaleItem;
use App\Models\SaleItemAllocation;
use App\Models\SaleReturnItemAllocation;
use App\Models\StockBatch;
use App\Models\StockMovement;
use RuntimeException;

class StockService
{
    /**
     * Buat stock_batch baru dari 1 baris purchase_order_item.
     * Dipanggil setelah PurchaseOrderItem tersimpan (barang dianggap
     * langsung masuk stok, sesuai aturan bisnis: barang bisa masuk
     * meski pembayaran belum lunas).
     */
    public function receiveFromPurchaseItem(PurchaseOrderItem $item, ?string $batchDate = null): StockBatch
    {
        $batchDate ??= $item->purchaseOrder->po_date;

        $batch = StockBatch::create([
            'product_id'              => $item->product_id,
            'purchase_order_item_id'  => $item->id,
            'batch_date'              => $batchDate,
            'buy_price'                => $item->buy_price,
            'qty_in'                   => $item->qty,
            'qty_remaining'            => $item->qty,
        ]);

        StockMovement::create([
            'product_id'      => $item->product_id,
            'stock_batch_id'  => $batch->id,
            'type'            => 'in',
            'qty'             => $item->qty,
            'movement_date'   => $batchDate,
            'reference_type'  => 'purchase_order_item',
            'reference_id'    => $item->id,
            'note'            => "Stok masuk dari PO #{$item->purchaseOrder->po_number}",
        ]);

        $this->syncProductQtyOnHand($item->product_id);

        return $batch;
    }

    /**
     * Potong stok dari batch-batch tertua (FIFO) untuk memenuhi 1 baris SaleItem.
     * Mengembalikan array detail alokasi (untuk dipakai membuat SaleItemAllocation).
     *
     * @return array<int, array{stock_batch_id:int, qty_taken:int, buy_price_at_time:float, hpp_subtotal:float}>
     *
     * @throws RuntimeException jika stok tidak mencukupi
     */
    public function allocateFifo(Product $product, int $qtyNeeded, string $movementDate, SaleItem $saleItem): array
    {
        if ($qtyNeeded <= 0) {
            throw new RuntimeException('Qty penjualan harus lebih dari 0.');
        }

        // Lock baris batch supaya aman dari race condition kalau 2 transaksi
        // penjualan terjadi bersamaan (wajib dipanggil di dalam DB::transaction()).
        $batches = $product->availableBatches()->lockForUpdate()->get();

        $totalAvailable = $batches->sum('qty_remaining');
        if ($totalAvailable < $qtyNeeded) {
            throw new RuntimeException(
                "Stok {$product->name} tidak mencukupi. Diminta {$qtyNeeded}, tersedia {$totalAvailable}."
            );
        }

        $remainingToTake = $qtyNeeded;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remainingToTake <= 0) {
                break;
            }

            $qtyFromThisBatch = min($batch->qty_remaining, $remainingToTake);

            // Kurangi sisa batch
            $batch->qty_remaining -= $qtyFromThisBatch;
            $batch->save();

            $hppSubtotal = $qtyFromThisBatch * $batch->buy_price;

            $allocations[] = [
                'stock_batch_id'     => $batch->id,
                'qty_taken'          => $qtyFromThisBatch,
                'buy_price_at_time'  => $batch->buy_price,
                'hpp_subtotal'       => $hppSubtotal,
            ];

            StockMovement::create([
                'product_id'      => $product->id,
                'stock_batch_id'  => $batch->id,
                'type'            => 'out',
                'qty'             => $qtyFromThisBatch,
                'movement_date'   => $movementDate,
                'reference_type'  => 'sale_item',
                'reference_id'    => $saleItem->id,
                'note'            => "Penjualan SO #{$saleItem->salesOrder->so_number} (FIFO dari batch #{$batch->id})",
            ]);

            $remainingToTake -= $qtyFromThisBatch;
        }

        $this->syncProductQtyOnHand($product->id);

        return $allocations;
    }

    /**
     * Kembalikan qty ke batch asal (dipakai untuk pembatalan/retur penjualan).
     * Menerima kembali array allocation records (SaleItemAllocation collection).
     */
    public function reverseAllocations(iterable $allocations, string $movementDate, string $note = 'Retur/pembatalan penjualan'): void
    {
        $affectedProductIds = [];

        foreach ($allocations as $allocation) {
            $batch = $allocation->stockBatch;
            $batch->qty_remaining += $allocation->qty_taken;
            $batch->save();

            StockMovement::create([
                'product_id'      => $batch->product_id,
                'stock_batch_id'  => $batch->id,
                'type'            => 'in',
                'qty'             => $allocation->qty_taken,
                'movement_date'   => $movementDate,
                'reference_type'  => 'sale_item',
                'reference_id'    => $allocation->sale_item_id,
                'note'            => $note,
            ]);

            $affectedProductIds[$batch->product_id] = true;
        }

        // Sync qty_on_hand untuk SETIAP produk yang batch-nya kena, bukan cuma
        // produk dari alokasi terakhir — 1 SO bisa berisi banyak produk berbeda.
        foreach (array_keys($affectedProductIds) as $productId) {
            $this->syncProductQtyOnHand($productId);
        }
    }

    /**
     * Kembalikan qty ke SATU batch tertentu (lewat SaleItemAllocation asalnya)
     * karena customer meretur sebagian/seluruh qty dari baris SO tsb. Beda dari
     * reverseAllocations() (yang selalu mengembalikan qty_taken PENUH — dipakai
     * utk edit/hapus SO), method ini menerima qty parsial sehingga bisa dipakai
     * utk retur sebagian, dan hasilnya dicatat via $returnAlloc (SaleReturnItemAllocation)
     * supaya jejak "sudah diretur berapa dari alokasi ini" tetap akurat.
     */
    public function returnFromCustomer(SaleItemAllocation $allocation, int $qty, string $returnDate, SaleReturnItemAllocation $returnAlloc): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Qty retur harus lebih dari 0.');
        }

        // Lock baris batch supaya aman dari race condition (wajib dipanggil
        // di dalam DB::transaction()).
        $batch = StockBatch::where('id', $allocation->stock_batch_id)->lockForUpdate()->first();

        if (! $batch) {
            throw new RuntimeException('Batch stok asal untuk alokasi ini tidak ditemukan.');
        }

        $batch->qty_remaining += $qty;
        $batch->save();

        StockMovement::create([
            'product_id'      => $batch->product_id,
            'stock_batch_id'  => $batch->id,
            'type'            => 'in',
            'qty'             => $qty,
            'movement_date'   => $returnDate,
            'reference_type'  => 'sale_return_item',
            'reference_id'    => $returnAlloc->sale_return_item_id,
            'note'            => "Retur penjualan dari customer — SO #{$allocation->saleItem->salesOrder->so_number}",
        ]);

        $this->syncProductQtyOnHand($batch->product_id);
    }

    /**
     * Kebalikan dari returnFromCustomer() — keluarkan lagi qty dari batch saat
     * sebuah retur SO dihapus/dibatalkan. TIDAK selalu aman seperti kebalikan
     * retur PO: barang yang sudah kembali ke stok bisa saja sudah kadung terjual
     * lagi atau diretur ke supplier, jadi divalidasi dulu terhadap qty_remaining
     * batch saat ini.
     *
     * @throws RuntimeException kalau qty batch tidak cukup untuk dikeluarkan lagi
     */
    public function undoReturnFromCustomer(SaleReturnItemAllocation $returnAlloc, string $movementDate): void
    {
        $batch = StockBatch::where('id', $returnAlloc->stock_batch_id)->lockForUpdate()->first();

        if (! $batch || $returnAlloc->qty > $batch->qty_remaining) {
            $available = $batch->qty_remaining ?? 0;
            throw new RuntimeException(
                "Retur tidak bisa dibatalkan: stok produk \"{$returnAlloc->saleReturnItem->product->name}\" dari batch ini sudah kadung terjual lagi atau diretur ke supplier ({$available} tersisa, butuh {$returnAlloc->qty})."
            );
        }

        $batch->qty_remaining -= $returnAlloc->qty;
        $batch->save();

        StockMovement::create([
            'product_id'      => $batch->product_id,
            'stock_batch_id'  => $batch->id,
            'type'            => 'out',
            'qty'             => $returnAlloc->qty,
            'movement_date'   => $movementDate,
            'reference_type'  => 'sale_return_item',
            'reference_id'    => $returnAlloc->sale_return_item_id,
            'note'            => 'Pembatalan retur penjualan',
        ]);

        $this->syncProductQtyOnHand($batch->product_id);
    }

    /**
     * Keluarkan qty dari batch stok hasil 1 baris PO item karena barang
     * dikembalikan (retur) ke supplier. Batch harus milik item yang sama,
     * dan hanya boleh sebesar qty_remaining yang masih ada di batch itu —
     * kalau barangnya sudah kadung terjual, sisa yang bisa diretur otomatis
     * lebih kecil (dicek oleh pemanggil / PurchaseReturnService).
     *
     * @throws RuntimeException kalau qty retur melebihi sisa batch yang ada
     */
    public function returnToSupplier(PurchaseOrderItem $item, int $qty, string $returnDate, PurchaseReturnItem $returnItem): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Qty retur harus lebih dari 0.');
        }

        // Lock baris batch supaya aman dari race condition kalau ada transaksi
        // penjualan/retur lain terjadi bersamaan (wajib dipanggil di dalam DB::transaction()).
        $batch = StockBatch::where('purchase_order_item_id', $item->id)->lockForUpdate()->first();

        if (! $batch || $qty > $batch->qty_remaining) {
            $available = $batch->qty_remaining ?? 0;
            throw new RuntimeException(
                "Qty retur untuk \"{$item->product->name}\" ({$qty}) melebihi sisa stok yang masih ada dari PO ini ({$available}). Barang yang sudah terjual tidak bisa diretur ke supplier."
            );
        }

        $batch->qty_remaining -= $qty;
        $batch->save();

        StockMovement::create([
            'product_id'      => $item->product_id,
            'stock_batch_id'  => $batch->id,
            'type'            => 'out',
            'qty'             => $qty,
            'movement_date'   => $returnDate,
            'reference_type'  => 'purchase_return_item',
            'reference_id'    => $returnItem->id,
            'note'            => "Retur ke supplier — PO #{$item->purchaseOrder->po_number}",
        ]);

        $this->syncProductQtyOnHand($item->product_id);
    }

    /**
     * Kebalikan dari returnToSupplier() — kembalikan qty ke batch asal saat
     * sebuah retur PO dihapus/dibatalkan. Selalu aman (menambah qty_remaining,
     * tidak pernah bikin negatif), jadi tidak perlu validasi batas atas.
     */
    public function undoReturnToSupplier(PurchaseReturnItem $returnItem, string $movementDate): void
    {
        $poItem = $returnItem->purchaseOrderItem;

        // Lock baris batch supaya aman dari race condition (wajib dipanggil
        // di dalam DB::transaction()).
        $batch = StockBatch::where('purchase_order_item_id', $poItem->id)->lockForUpdate()->first();

        if (! $batch) {
            throw new RuntimeException(
                "Batch stok untuk produk \"{$returnItem->product->name}\" tidak ditemukan, retur tidak bisa dibatalkan."
            );
        }

        $batch->qty_remaining += $returnItem->qty;
        $batch->save();

        StockMovement::create([
            'product_id'      => $returnItem->product_id,
            'stock_batch_id'  => $batch->id,
            'type'            => 'in',
            'qty'             => $returnItem->qty,
            'movement_date'   => $movementDate,
            'reference_type'  => 'purchase_return_item',
            'reference_id'    => $returnItem->id,
            'note'            => "Pembatalan retur {$returnItem->purchaseReturn->return_number} — PO #{$poItem->purchaseOrder->po_number}",
        ]);

        $this->syncProductQtyOnHand($returnItem->product_id);
    }

    /**
     * Cek apakah batch stok hasil 1 baris PO item sudah kepakai (terjual)
     * sebagian atau seluruhnya. Dipakai buat menolak edit/hapus PO yang
     * barangnya sudah kadung terjual, walau PO itu sendiri belum dibayar
     * sama sekali (status pembayaran & pergerakan stok itu dua hal terpisah).
     */
    public function isPurchaseItemBatchUsed(PurchaseOrderItem $item): bool
    {
        $batch = $item->stockBatch;

        if (! $batch) {
            return false;
        }

        return $batch->qty_remaining < $batch->qty_in;
    }

    /**
     * Hapus 1 batch stok hasil PO item beserta jejak stock_movements-nya,
     * lalu sync ulang qty_on_hand produknya. HANYA boleh dipanggil kalau
     * isPurchaseItemBatchUsed() sudah dipastikan false (batch belum kepakai
     * sama sekali) — dicek di pemanggil (PurchaseOrderService).
     */
    public function removeUnusedPurchaseBatch(PurchaseOrderItem $item): void
    {
        $batch = $item->stockBatch;

        if (! $batch) {
            return;
        }

        $productId = $batch->product_id;

        StockMovement::where('reference_type', 'purchase_order_item')
            ->where('reference_id', $item->id)
            ->delete();

        $batch->delete();

        $this->syncProductQtyOnHand($productId);
    }

    /**
     * Sinkronkan kolom cache products.qty_on_hand dari SUM(stock_batches.qty_remaining).
     * Selalu panggil ini setiap kali qty_remaining berubah, jangan pernah
     * update qty_on_hand secara manual di tempat lain.
     */
    public function syncProductQtyOnHand(int $productId): void
    {
        $total = StockBatch::where('product_id', $productId)->sum('qty_remaining');
        Product::where('id', $productId)->update(['qty_on_hand' => $total]);
    }

    /**
     * Breakdown stok per batch untuk 1 produk (dipakai Laporan Stok).
     */
    public function batchBreakdown(Product $product)
    {
        return $product->stockBatches()
            ->where('qty_remaining', '>', 0)
            ->orderBy('batch_date')
            ->get(['id', 'batch_date', 'buy_price', 'qty_in', 'qty_remaining']);
    }
}
