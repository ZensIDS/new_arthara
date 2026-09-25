<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas yang dipakai untuk membayar biaya ini (saldo kas ini akan berkurang
        // sebesar 'amount'). Nullable supaya kompatibel dengan expense lama yang
        // dibuat sebelum fitur kas ini ada — expense baru (input manual dari
        // halaman Pengeluaran) wajib mengisi ini, divalidasi di
        // StoreExpenseRequest / UpdateExpenseRequest. nullOnDelete supaya kalau
        // kas dihapus, riwayat expense lama tidak ikut hilang.
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('cash_id')->nullable()->after('sales_order_id')
                ->constrained('cashes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_id');
        });
    }
};