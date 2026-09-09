<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('sell_price', 15, 2); // harga jual per unit, dinamis/nego per transaksi
            $table->decimal('subtotal', 15, 2);   // qty * sell_price
            // hpp_subtotal = SUM(sale_item_allocations.hpp_subtotal) untuk baris ini, di-cache
            $table->decimal('hpp_subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
