<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ledger kas: satu baris untuk setiap kejadian kas masuk/keluar yang REALISASI
        // (bukan piutang/hutang yang masih outstanding). Dipakai agar Laporan Arus Kas
        // tidak perlu query gabungan 3 tabel (sales_payments, purchase_payments, expenses)
        // setiap kali diakses -> lebih cepat & konsisten (single source of truth report).
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('direction', ['in', 'out']); // in = kas masuk, out = kas keluar
            $table->decimal('amount', 15, 2);
            $table->string('source_type'); // 'sales_payment', 'purchase_payment', 'expense'
            $table->unsignedBigInteger('source_id');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['transaction_date', 'direction']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
