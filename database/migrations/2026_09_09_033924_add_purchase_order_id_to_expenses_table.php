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
            // terikat PO manapun. Yang terisi hanya expense yang dibuat otomatis dari
            // form "Biaya Lainnya" pada Purchase Order (packing, ongkir, dll).
            // cascadeOnDelete supaya kalau PO dihapus, biaya-biaya turunannya (dan
            // cash_flow terkait, dibersihkan manual di PurchaseOrderService) ikut hilang.
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('expense_category_id')
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropIndex(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};
