<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('return_date');
            // total_amount = cache SUM(purchase_return_items.subtotal), disinkronkan oleh PurchaseReturnService
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
