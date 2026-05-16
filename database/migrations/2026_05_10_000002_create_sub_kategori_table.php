<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUB_KATEGORI — 42 baris fixed (treatment dropdown navbar Treatwell):
 *   Hair (6), Hair Removal (6), Massage (6), Nails (6), Face (6), Body (6), Men's (6)
 *
 * Field:
 *   - name             : display ("Blow Dry", "Pedicure")
 *   - slug             : URL VIYGO (unique). "Men's Haircut" pakai suffix
 *                        '-hair' / '-mens' karena muncul di 2 grup berbeda.
 *   - deskripsi        : opsional
 *   - icon_url         : opsional
 *   - treatwell_slug   : slug Treatwell utk scraper URL builder
 *                        (mis. "blow-dry" → /treatment-blow-dry/)
 *   - is_active        : soft-disable
 *
 * Item navbar yg TIDAK masuk DB (handled by query/route):
 *   - 6 link "See all X treatments" → route /kategori/{slug}
 *   - 1 link "Barbers" di Mens     → route /kategori/mens?filter=barbers
 *
 * Migration ini juga ATTACH FK constraint `service.id_sub_kategori` →
 * `sub_kategori.id_sub_kategori`. Kolom-nya sudah dibuat di migration
 * service awal (nullable, no FK), tinggal attach constraint di sini krn
 * sub_kategori baru exist sekarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_kategori', function (Blueprint $table) {
            $table->id('id_sub_kategori');
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('treatwell_slug', 180)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Attach FK service.id_sub_kategori → sub_kategori (delayed dari
        // migration service awal karena sub_kategori belum exist saat itu).
        if (Schema::hasTable('service') && Schema::hasColumn('service', 'id_sub_kategori')) {
            Schema::table('service', function (Blueprint $table) {
                $table->foreign('id_sub_kategori')
                      ->references('id_sub_kategori')
                      ->on('sub_kategori')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service') && Schema::hasColumn('service', 'id_sub_kategori')) {
            Schema::table('service', function (Blueprint $table) {
                try { $table->dropForeign(['id_sub_kategori']); } catch (\Throwable $e) {}
            });
        }
        Schema::dropIfExists('sub_kategori');
    }
};
