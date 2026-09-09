<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('so_date');
            // total_amount, total_hpp, paid_amount adalah cache hasil agregasi sale_items & sales_payments
            $table->decimal('total_amount', 15, 2)->default(0); // total penjualan (harga jual)
            $table->decimal('total_hpp', 15, 2)->default(0);    // total HPP hasil alokasi FIFO
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('so_date');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
