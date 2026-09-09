<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SKU dihapus dari modul Produk atas permintaan user — SKU (kode identitas produk)
 * berbeda konsep dari batch (stock_batches, yang jadi inti perhitungan FIFO), tapi
 * karena tidak dibutuhkan dan bikin bingung, kolom ini kita drop dari database.
 *
 * Catatan: file migration asli pembuat tabel `products` tidak ada di dalam export
 * project yang dikirim, jadi ini dibuat sebagai migration TAMBAHAN (bukan mengedit
 * migration lama). Taruh file ini di folder `database/migrations/` project kamu,
 * lalu jalankan `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropColumn('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->unique()->after('category_id');
            }
        });
    }
};
