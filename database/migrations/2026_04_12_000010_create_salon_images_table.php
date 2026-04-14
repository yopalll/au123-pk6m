<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salon_images', function (Blueprint $table) {
             // Primary Key
        $table->id('id_salon_image');
        $table->foreignId('id_salon')
        ->constrained('salon', 'id_salon')
        ->cascadeOnDelete();
        // Foreign Key ke tabel salon

        // Kolom Data
        $table->string('image_url');
        $table->boolean('is_primary')->default(false); // Menggunakan boolean untuk tipe bool
        $table->integer('urutan'); // Menggunakan integer untuk tipe int

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salon_images');
    }
};
