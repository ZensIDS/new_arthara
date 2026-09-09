<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori untuk pemasukan lain di luar penjualan — mis. "Modal Disetor",
     * "Pinjaman", "Hibah", "Pendapatan Sewa", dll. Strukturnya sengaja dibuat
     * identik dengan expense_categories supaya polanya konsisten.
     */
    public function up(): void
    {
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_categories');
    }
};
