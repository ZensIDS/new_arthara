<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master "Bagi Hasil": daftar orang (owner/partner) beserta persentase
     * bagiannya dari Laba Bersih. Dipakai untuk menampilkan pembagian laba
     * per orang di Ringkasan Laba Rugi (report & dashboard) — TIDAK
     * memengaruhi angka Laba Bersih itu sendiri, murni pembagian/alokasi
     * dari laba yang sudah dihitung.
     */
    public function up(): void
    {
        Schema::create('profit_shares', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Persentase bagian dari Laba Bersih, mis. 40.00 = 40%.
            $table->decimal('percentage', 5, 2);
            // Supaya orang yang sudah tidak aktif bisa "dinonaktifkan" tanpa
            // dihapus (riwayat tetap ada), tanpa ikut dihitung lagi ke depan.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_shares');
    }
};
