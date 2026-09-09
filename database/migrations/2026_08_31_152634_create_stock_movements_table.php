<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail semua mutasi stok (masuk & keluar), independen dari stock_batches
        // agar histori tetap utuh walau batch sudah habis. Polymorphic ke sumber transaksi.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->integer('qty'); // selalu positif; makna in/out ditentukan kolom type
            $table->date('movement_date');
            $table->string('reference_type'); // 'purchase_order_item', 'sale_item', 'manual_adjustment'
            $table->unsignedBigInteger('reference_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
