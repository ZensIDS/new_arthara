<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini adalah jantung logika FIFO: setiap sale_item bisa "memakan"
        // lebih dari satu stock_batch. Satu baris = satu potongan qty dari satu batch.
        Schema::create('sale_item_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('stock_batch_id')->constrained('stock_batches')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('qty_taken'); // qty yang diambil dari batch ini
            $table->decimal('buy_price_at_time', 15, 2); // snapshot harga beli batch saat alokasi (HPP riil)
            $table->decimal('hpp_subtotal', 15, 2); // qty_taken * buy_price_at_time
            $table->timestamps();

            $table->index('stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_allocations');
    }
};
