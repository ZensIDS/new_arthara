<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag per KATEGORI pemasukan: apakah pemasukan di kategori ini ikut
     * dihitung sebagai penambah Laba Bersih di Laporan Laba Rugi, atau
     * cuma transaksi permodalan/pendanaan yang seharusnya tidak masuk laba
     * (mis. Modal Disetor, Pinjaman Bank) meski tetap tercatat sebagai kas
     * masuk di ledger cash_flows.
     *
     * Default true, karena kategori "wajar" (jasa perbaikan, sisa ongkir,
     * dll) memang lebih umum dipakai dan seharusnya masuk laba rugi.
     * Kategori seperti Modal/Pinjaman perlu di-set manual jadi false oleh
     * superadmin lewat halaman Kategori Pemasukan.
     */
    public function up(): void
    {
        Schema::table('income_categories', function (Blueprint $table) {
            $table->boolean('affects_profit_loss')->default(true)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('income_categories', function (Blueprint $table) {
            $table->dropColumn('affects_profit_loss');
        });
    }
};
