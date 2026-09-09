<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('return_date');
            // total_amount & total_hpp adalah cache SUM(sale_return_items.*), disinkronkan oleh SalesReturnService
            $table->decimal('total_amount', 15, 2)->default(0); // nilai jual yang dikembalikan (mengurangi total_amount SO)
            $table->decimal('total_hpp', 15, 2)->default(0);    // nilai HPP yang dikembalikan ke stok (mengurangi total_hpp SO)
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
