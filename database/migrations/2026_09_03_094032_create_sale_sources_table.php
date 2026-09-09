<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Seed 3 asal penjualan yang sebelumnya hardcode di SalesOrder::SOURCES,
        // supaya data existing (migrasi berikutnya) tetap bisa dipetakan.
        $now = now();
        DB::table('sale_sources')->insert([
            ['id' => 1, 'name' => 'Offline', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'WhatsApp', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Shopee', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_sources');
    }
};
