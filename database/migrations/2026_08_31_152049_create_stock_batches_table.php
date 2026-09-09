<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            // Satu batch = satu baris purchase_order_item (1 PO item = 1 batch masuk stok)
            $table->foreignId('purchase_order_item_id')
                ->unique()
                ->constrained('purchase_order_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('batch_date'); // tanggal masuk stok, dipakai untuk urutan FIFO
            $table->decimal('buy_price', 15, 2); // harga beli asli batch ini (untuk HPP riil)
            $table->integer('qty_in'); // qty awal masuk
            $table->integer('qty_remaining'); // sisa qty yang belum terjual, wajib >= 0
            $table->timestamps();

            // Urutan FIFO diambil dari batch_date lalu id (tie-breaker) secara ASC
            $table->index(['product_id', 'batch_date']);
            $table->index(['product_id', 'qty_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
