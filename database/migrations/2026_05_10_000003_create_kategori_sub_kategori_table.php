<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot M:N kategori ↔ sub_kategori.
 *
 * 1 kategori utama (Hair) punya 6 sub_kategori. 1 sub_kategori (Men's
 * Haircut) bisa terdaftar di 2 kategori (Hair + Men's). Total pivot rows
 * = 42 (sesuai navbar Treatwell):
 *   Hair: 6, Hair Removal: 6, Massage: 6, Nails: 6, Face: 6, Body: 6,
 *   Men's: 6 (excluding "Barbers" & "Men's Haircut" diduplicate dgn Hair)
 *
 * Field `urutan` menyimpan urutan sub_kategori dalam dropdown navbar
 * (1..N per kategori).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_sub_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kategori')
                  ->constrained('kategori', 'id_kategori')
                  ->cascadeOnDelete();
            $table->foreignId('id_sub_kategori')
                  ->constrained('sub_kategori', 'id_sub_kategori')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['id_kategori', 'id_sub_kategori']);
            $table->index('id_sub_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_sub_kategori');
    }
};
