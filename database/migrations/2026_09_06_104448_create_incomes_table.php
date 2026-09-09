<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemasukan lain di luar penjualan — mis. suntikan modal, pinjaman,
     * pendapatan sewa, dll. Strukturnya paralel dengan tabel expenses
     * (kebalikan arah kasnya: expenses = kas keluar, incomes = kas masuk).
     * Setiap baris di sini otomatis dicatat juga ke ledger cash_flows
     * (direction = 'in') lewat IncomeService — lihat CashFlowService.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_category_id')
                ->constrained('income_categories')
                ->restrictOnDelete(); // kategori tidak boleh dihapus selama masih dipakai (dicek juga di controller)
            $table->date('income_date');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('income_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
