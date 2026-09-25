<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas yang menerima pemasukan ini (saldo kas ini akan bertambah sebesar
        // 'amount'). Nullable supaya kompatibel dengan income lama yang dibuat
        // sebelum fitur kas ini ada — income baru wajib mengisi ini, divalidasi
        // di StoreIncomeRequest / UpdateIncomeRequest. nullOnDelete supaya kalau
        // kas dihapus, riwayat income lama tidak ikut hilang.
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('cash_id')->nullable()->after('income_category_id')
                ->constrained('cashes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_id');
        });
    }
};