<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mitra_applications', function (Blueprint $table) {
            $table->id('id_application');
            $table->string('nama_salon', 200);
            $table->string('nama_pemilik', 200);
            $table->string('email', 200)->index();
            $table->string('phone', 50);
            $table->foreignId('id_kota')
                ->nullable()
                ->constrained('kota', 'id_kota')
                ->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->enum('status', ['new', 'contacted', 'approved', 'rejected'])
                ->default('new')
                ->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitra_applications');
    }
};
