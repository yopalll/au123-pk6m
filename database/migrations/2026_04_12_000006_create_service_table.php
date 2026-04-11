<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service', function (Blueprint $table) {
            $table->id('id_service');
            $table->foreignId('id_salon')->constrained('salon', 'id_salon')->cascadeOnDelete();
            $table->foreignId('id_kategori')->constrained('kategori', 'id_kategori');
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('durasi');
            $table->decimal('harga', 12, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_salon');
            $table->index('id_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};
