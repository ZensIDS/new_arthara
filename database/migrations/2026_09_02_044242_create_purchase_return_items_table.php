<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnUpdate()->cascadeOnDelete();
            // Item PO asal yang diretur — 1 baris retur selalu mengacu ke 1 baris PO item
            // (produk & harga beli diambil dari sini, bukan input bebas).
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty'); // qty yang dikembalikan ke supplier
            $table->decimal('buy_price', 15, 2); // disalin dari purchase_order_items.buy_price saat retur dibuat
            $table->decimal('subtotal', 15, 2); // qty * buy_price
            $table->timestamps();

            $table->index('product_id');
            $table->index('purchase_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
