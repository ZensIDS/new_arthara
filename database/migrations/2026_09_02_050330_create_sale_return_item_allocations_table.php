<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sama seperti sale_item_allocations adalah jantung FIFO saat menjual,
        // tabel ini adalah jantung FIFO saat retur: 1 baris retur item bisa
        // mengembalikan qty ke lebih dari satu batch asal (kalau sale_item-nya
        // dulu juga diambil dari lebih dari satu batch). Satu baris = satu
        // potongan qty yang dikembalikan ke satu sale_item_allocation/batch asal,
        // dipakai juga untuk mencegah retur melebihi qty yang benar-benar
        // pernah diambil dari batch itu (qty_taken - qty yang sudah diretur).
        Schema::create('sale_return_item_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_item_id')->constrained('sale_return_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sale_item_allocation_id')->constrained('sale_item_allocations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('stock_batch_id')->constrained('stock_batches')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty'); // qty yang dikembalikan ke batch ini
            $table->decimal('buy_price_at_time', 15, 2); // disalin dari sale_item_allocations.buy_price_at_time
            $table->decimal('hpp_subtotal', 15, 2); // qty * buy_price_at_time

            $table->timestamps();

            $table->index('sale_item_allocation_id');
            $table->index('stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_item_allocations');
    }
};
