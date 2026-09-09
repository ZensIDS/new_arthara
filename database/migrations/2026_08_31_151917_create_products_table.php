<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('unit')->default('pcs'); // pcs, unit, set, dll
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            // qty_on_hand adalah kolom cache (denormalized) hasil SUM(stock_batches.qty_remaining)
            // wajib disinkronkan lewat StockService, JANGAN diedit manual di luar service.
            $table->integer('qty_on_hand')->default(0);
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
