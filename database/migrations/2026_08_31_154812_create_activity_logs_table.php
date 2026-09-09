<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Siapa yang melakukan aksi. Nullable & tanpa FK constraint (SET NULL manual di app)
            // supaya log tetap utuh walau user dihapus, dan supaya seeding/command bisa jalan
            // tanpa auth (mis. proses cron/import).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Model apa yang kena aksi, pakai morphs manual (bukan trait) contoh:
            // subject_type = 'App\Models\PurchaseOrder', subject_id = 5
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('module')->nullable();  // 'purchase_order', 'sales_order', 'stock', 'expense', dll
            $table->string('action');               // 'created', 'updated', 'deleted', atau custom: 'fifo_allocated'
            $table->text('description')->nullable(); // kalimat siap tampil: "PO-2024-001 dibuat oleh Budi"

            // Snapshot perubahan data, diisi otomatis oleh Observer global
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();

            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->nullable(); // cukup created_at, log tidak pernah di-update

            $table->index(['subject_type', 'subject_id']);
            $table->index('module');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
