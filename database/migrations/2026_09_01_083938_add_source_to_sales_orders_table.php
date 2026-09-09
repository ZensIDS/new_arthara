<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // Asal transaksi penjualan (channel), murni informasi/pelaporan —
            // tidak dipakai dalam perhitungan apa pun. Pakai string (bukan enum)
            // supaya daftar channel gampang ditambah nanti tanpa migration baru;
            // daftar yang divalidasi ada di App\Models\SalesOrder::SOURCES.
            $table->string('source')->default('offline')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
