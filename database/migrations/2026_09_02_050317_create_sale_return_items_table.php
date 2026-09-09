<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnUpdate()->cascadeOnDelete();
            // Baris sale_item asal yang diretur — 1 baris retur selalu mengacu ke 1 baris SO item
            // (produk & harga jual diambil dari sini, bukan input bebas).
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty'); // qty yang dikembalikan oleh customer
            $table->decimal('sell_price', 15, 2); // disalin dari sale_items.sell_price saat retur dibuat
            $table->decimal('subtotal', 15, 2);   // qty * sell_price
            // hpp_subtotal = SUM(sale_return_item_allocations.hpp_subtotal) untuk baris ini, di-cache
            $table->decimal('hpp_subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->index('sale_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
