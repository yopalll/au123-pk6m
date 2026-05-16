<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KATEGORI — 7 baris fixed (level utama navbar Treatwell):
 *   Hair, Hair Removal, Massage, Nails, Face, Body, Men's
 *
 * Field:
 *   - name             : display ("Hair", "Men's")
 *   - slug             : URL VIYGO ("hair", "mens"), unique
 *   - deskripsi        : opsional, tampil di hero kategori page
 *   - icon_url         : opsional
 *   - treatwell_slug   : slug Treatwell utk scraper URL builder
 *                        (mis. "face-beauty" → /treatment-group-face-beauty/)
 *   - urutan           : urutan tampilan di navbar (1..7)
 *   - is_active        : soft-disable kategori tanpa hapus row
 *
 * Diisi oleh `KategoriSeeder.php` dgn 7 baris fixed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id('id_kategori');
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('treatwell_slug', 120)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
