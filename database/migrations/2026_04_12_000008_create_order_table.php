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
        Schema::create('order', function (Blueprint $table) {
             // Primary Key
            $table->id('id_order'); 

            // Foreign Keys (BigInt sesuai gambar)
            $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->foreignId('id_salon')->constrained('salon', 'id_salon')->cascadeOnDelete();
            $table->foreignId('id_promo')->constrained('promo', 'id_promo')->cascadeOnDelete()->nullable(); // Dibuat nullable jika promo opsional

            // Kolom Lainnya
            $table->string('kode_order', 50)->unique(); // Sesuai gambar, dengan panjang 50 karakter
            $table->date('date_order');
            $table->decimal('total_pembayaran', 12, 2); // 12 digit total, 2 di belakang koma
            $table->decimal('total_diskon', 12, 2)->default(0); // Diskon default 0
            $table->enum('status', ['pending', 'success', 'canceled']); // Sesuaikan isi enumnya

            $table->timestamps(); // Opsional: untuk created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
