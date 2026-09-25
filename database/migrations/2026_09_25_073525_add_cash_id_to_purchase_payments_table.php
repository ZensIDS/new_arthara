<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas yang dipakai untuk membayar termin ini (saldo kas ini akan berkurang
        // sebesar 'amount'). Nullable supaya kompatibel dengan payment lama (kalau
        // ada) yang dibuat sebelum fitur kas ini ada — payment baru wajib mengisi ini
        // (divalidasi di StorePurchasePaymentRequest / UpdatePurchasePaymentRequest).
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->foreignId('cash_id')->nullable()->after('purchase_order_id')
                ->constrained('cashes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_id');
        });
    }
};