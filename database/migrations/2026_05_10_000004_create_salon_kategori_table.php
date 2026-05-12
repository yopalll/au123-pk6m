<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot M:N salon ↔ kategori.
 *
 * Menjawab "salon mana saja yang menyediakan kategori X (mis. Pedicure)?"
 * tanpa harus join ke tabel `service`.
 *
 * Diisi otomatis oleh SalonKategoriSeeder dari distinct(service.id_kategori
 * per id_salon) — atau langsung oleh scraper Go via JSON pivot dump.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_salon')
                  ->constrained('salon', 'id_salon')
                  ->cascadeOnDelete();
            $table->foreignId('id_kategori')
                  ->constrained('kategori', 'id_kategori')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_salon', 'id_kategori']);
            $table->index('id_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_kategori');
    }
};
