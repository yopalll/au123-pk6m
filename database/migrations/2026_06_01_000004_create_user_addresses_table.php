<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id('id_address');
            $table->unsignedBigInteger('id_user');
            $table->string('label'); // "Rumah", "Kantor"
            $table->string('nama_penerima');
            $table->string('phone');
            $table->text('alamat_lengkap');
            $table->string('kota'); // nama kota dari api.co.id
            $table->string('kota_id'); // ID kota dari api.co.id
            $table->string('provinsi');
            $table->string('provinsi_id');
            $table->string('kode_pos');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // CATATAN: TIDAK FK ke tabel kota V1 — domain data berbeda (api.co.id vs Treatwell)
            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
