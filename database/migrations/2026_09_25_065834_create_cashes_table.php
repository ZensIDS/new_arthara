<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master akun kas (mis. "Kas Toko", "BCA Utama", dll). Setiap transaksi
        // (PO, SO, Expense, Retur, dll) nantinya akan memilih salah satu kas ini
        // sebagai sumber saldo yang dipakai (trx keluar) atau yang bertambah (trx masuk).
        Schema::create('cashes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cash', 'bank'])->default('cash'); // cash = tunai, bank = rekening bank
            $table->string('account_number')->nullable(); // no. rekening, khusus type bank
            $table->decimal('initial_balance', 15, 2)->default(0); // saldo awal saat kas dibuat
            $table->decimal('current_balance', 15, 2)->default(0); // saldo berjalan (akan diupdate saat modul transaksi kas dibuat)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashes');
    }
};