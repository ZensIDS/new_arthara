<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas yang menerima pembayaran termin SO ini (saldo kas ini akan bertambah
        // sebesar 'amount'). Nullable supaya kompatibel dengan payment lama (kalau
        // ada) yang dibuat sebelum fitur kas ini ada — payment baru wajib mengisi ini
        // (divalidasi di StoreSalesPaymentRequest / UpdateSalesPaymentRequest).
        // Pola sama persis seperti add_cash_id_to_purchase_payments_table.
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->foreignId('cash_id')->nullable()->after('sales_order_id')
                ->constrained('cashes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_id');
        });
    }
};