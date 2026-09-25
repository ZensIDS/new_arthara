<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kas mana yang saldonya bergerak untuk baris ledger ini. Ditaruh langsung
        // di cash_flows (bukan cuma diturunkan dari source) supaya CashFlowService
        // tetap generic untuk semua jenis source (PurchasePayment, SalesPayment,
        // Expense, PurchaseReturn/SalesReturn refund, dll) tanpa perlu tahu struktur
        // masing-masing source. Nullable untuk kompatibel dengan entry lama.
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->foreignId('cash_id')->nullable()->after('direction')
                ->constrained('cashes')->nullOnDelete();

            $table->index('cash_id');
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_id');
        });
    }
};