<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->enum('method', ['cash', 'transfer', 'other'])->default('cash');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
