<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot M:N salon ↔ sub_kategori.
 *
 * Diisi LANGSUNG oleh scraper Go dari context URL listing:
 *   - Saat scraper visiting URL /places/treatment-{slug}/in-{kota}-uk/,
 *     SEMUA salon yg ditemukan di listing itu → tagged ke sub_kategori
 *     yang sesuai (mis. URL treatment-blow-dry → tag salon ke sub #2).
 *
 * Berbeda dgn pivot salon_kategori (derived dari distinct service.id_kategori),
 * pivot ini eksplisit menjawab: "salon X muncul di listing sub_kategori
 * Y di Treatwell?" — bahkan kalau tidak ada service-nya yg match keyword.
 *
 * Use case: query "salon mana saja yg menyediakan Blow Dry?" lebih
 * akurat dari pivot ini drpd dari distinct service.id_sub_kategori.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_sub_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_salon')
                  ->constrained('salon', 'id_salon')
                  ->cascadeOnDelete();
            $table->foreignId('id_sub_kategori')
                  ->constrained('sub_kategori', 'id_sub_kategori')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_salon', 'id_sub_kategori']);
            $table->index('id_sub_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_sub_kategori');
    }
};
