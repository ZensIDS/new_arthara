<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // customer_id memang sudah opsional di form & validasi (StoreSalesOrderRequest
        // / UpdateSalesOrderRequest sudah 'nullable'), tapi kolomnya di DB masih NOT
        // NULL — jadi submit tanpa customer akan gagal di level database. Di-nullable-
        // kan di sini, bukan pakai ->nullable()->change() (butuh doctrine/dbal yang
        // biasanya belum ter-install), jadi drop dulu foreign key-nya, ubah kolom via
        // raw SQL, baru pasang lagi foreign key-nya (foreign key MySQL tetap boleh
        // berisi NULL walau kolom "wajib referensi" sepanjang ada isinya).
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        DB::statement('ALTER TABLE sales_orders MODIFY customer_id BIGINT UNSIGNED NULL');

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        // Jaga-jaga kalau ada baris customer_id null (dibuat setelah kolom ini
        // nullable) — default-kan dulu ke customer pertama yang ada supaya
        // ALTER TABLE ... NOT NULL di bawah tidak gagal.
        $fallbackCustomerId = DB::table('customers')->orderBy('id')->value('id');
        if ($fallbackCustomerId) {
            DB::table('sales_orders')->whereNull('customer_id')->update(['customer_id' => $fallbackCustomerId]);
        }

        DB::statement('ALTER TABLE sales_orders MODIFY customer_id BIGINT UNSIGNED NOT NULL');

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }
};
