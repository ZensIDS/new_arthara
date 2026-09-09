<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Nullable: expense biasa (input manual dari halaman Pengeluaran) tidak
            // terikat SO manapun. Yang terisi hanya expense yang dibuat otomatis dari
            // form "Biaya Lainnya" pada Sales Order (packing, ongkir, dll) — pola yang
            // sama seperti purchase_order_id di atas.
            // cascadeOnDelete supaya kalau SO dihapus, biaya-biaya turunannya (dan
            // cash_flow terkait, dibersihkan manual di SalesOrderService) ikut hilang.
            $table->foreignId('sales_order_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('sales_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropIndex(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
