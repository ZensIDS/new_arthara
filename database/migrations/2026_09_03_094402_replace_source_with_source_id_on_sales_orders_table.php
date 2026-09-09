<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Mapping nilai lama (kolom string 'source') -> id di 'sale_sources',
    // sesuai urutan seed di migration sebelumnya.
    protected array $legacyMap = [
        'offline'  => 1,
        'whatsapp' => 2,
        'shopee'   => 3,
    ];

    public function up(): void
    {
        // Idempotent — migration sebelumnya sempat gagal di tengah jalan (ALTER TABLE
        // di MySQL tidak transaksional), jadi tiap langkah dicek dulu sebelum dieksekusi
        // supaya aman dijalankan ulang tanpa kena error "Duplicate column"/"column not found".
        if (! Schema::hasColumn('sales_orders', 'source_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->foreignId('source_id')->nullable()->after('note')
                    ->constrained('sale_sources')->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('sales_orders', 'source')) {
            // Backfill dari kolom lama sebelum di-drop, supaya data historis tidak hilang.
            foreach ($this->legacyMap as $oldValue => $newId) {
                DB::table('sales_orders')->where('source', $oldValue)->update(['source_id' => $newId]);
            }

            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        // Jaga-jaga kalau ada baris yang masih null (data kotor / migrasi lama) —
        // default-kan ke 'Offline' (id 1) supaya kolom source_id tidak ada yang null
        // sebelum di-set NOT NULL.
        DB::table('sales_orders')->whereNull('source_id')->update(['source_id' => 1]);

        // Ubah source_id jadi NOT NULL pakai raw SQL (bukan ->change()) supaya
        // tidak butuh paket doctrine/dbal yang biasanya belum ter-install.
        DB::statement('ALTER TABLE sales_orders MODIFY source_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('source')->nullable()->after('note');
        });

        $reverseMap = array_flip($this->legacyMap);
        foreach ($reverseMap as $id => $oldValue) {
            DB::table('sales_orders')->where('source_id', $id)->update(['source' => $oldValue]);
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_id');
        });
    }
};
