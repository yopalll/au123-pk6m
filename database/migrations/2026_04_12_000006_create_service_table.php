<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SERVICE — instance treatment per salon (mis. "Premium Bob Cut £45").
 *
 * Field klasifikasi (keduanya nullable agar fleksibel):
 *   - id_kategori      : 1..7 (kategori utama; FK constraint dibuat di sini)
 *   - id_sub_kategori  : 1..42 (sub_kategori; FK constraint dibuat NANTI di
 *                        migration sub_kategori karena tabel target belum ada)
 *
 * Field ini di-resolve oleh scraper Go (URL-based, lihat scraper.go) atau
 * di-tag manual via Filament admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service', function (Blueprint $table) {
            $table->id('id_service');
            $table->foreignId('id_salon')
                  ->constrained('salon', 'id_salon')
                  ->cascadeOnDelete();

            // Klasifikasi (keduanya nullable; FK id_sub_kategori di-attach
            // di migration 2026_05_10_000002_create_sub_kategori_table).
            $table->foreignId('id_kategori')
                  ->nullable()
                  ->constrained('kategori', 'id_kategori')
                  ->nullOnDelete();
            $table->unsignedBigInteger('id_sub_kategori')->nullable();

            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('durasi');
            $table->decimal('harga', 12, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_salon');
            $table->index('id_kategori');
            $table->index('id_sub_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};
