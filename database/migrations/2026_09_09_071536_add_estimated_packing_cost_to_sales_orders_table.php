<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // Estimasi biaya packing yang diinput manual saat create/edit SO —
            // murni buat gambaran untung-rugi di halaman detail & daftar SO,
            // TIDAK ikut dihitung ke total_amount/paid_amount atau catatan
            // keuangan manapun (bukan Expense, bukan cash_flow).
            $table->decimal('estimated_packing_cost', 15, 2)->default(0)->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('estimated_packing_cost');
        });
    }
};
