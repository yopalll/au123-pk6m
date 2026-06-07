<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empty_returns', function (Blueprint $table) {
            $table->id('id_return');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_salon')->nullable();
            $table->string('nama_produk'); // untuk input manual
            $table->integer('jumlah');
            $table->enum('metode', ['dropoff', 'pickup']);
            $table->text('alamat_pickup')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'picked_up', 'received'])->default('pending');
            $table->integer('poin_earned')->default(0);
            $table->text('catatan_admin')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('id_product')->references('id_product')->on('products')->nullOnDelete();
            $table->foreign('id_salon')->references('id_salon')->on('salon')->nullOnDelete();
            $table->foreign('verified_by')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empty_returns');
    }
};
