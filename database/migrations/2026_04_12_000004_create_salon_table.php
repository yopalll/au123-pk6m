<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon', function (Blueprint $table) {
            $table->id('id_salon');
            $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->foreignId('id_kota')->constrained('kota', 'id_kota');
            $table->string('nama_salon', 150);
            $table->text('alamat');
            $table->text('deskripsi')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->time('opening_time');
            $table->time('closing_time');
            $table->string('image_url')->nullable();
            $table->string('maps_url')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_review')->default(0);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_kota');
            $table->index('status');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon');
    }
};
