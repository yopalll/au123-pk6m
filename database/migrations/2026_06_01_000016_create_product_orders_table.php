<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id('id_product_order');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_address');
            $table->unsignedBigInteger('id_promo')->nullable();
            // Format: "VYG-S-XXXXXX" — berbeda dari booking V1 "VYG-XXXXXX"
            $table->string('kode_order')->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('biaya_kirim', 10, 2);
            $table->decimal('total_diskon', 10, 2)->default(0);
            $table->integer('poin_digunakan')->default(0);
            $table->decimal('potongan_poin', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->string('kurir'); // 'jne','jnt','sicepat','pos'
            $table->string('layanan_kirim'); // 'REG','OKE','YES','EZ', dll.
            $table->string('estimasi_tiba')->nullable(); // '2-3 hari'
            $table->string('resi')->nullable();
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('id_address')->references('id_address')->on('user_addresses');
            $table->foreign('id_promo')->references('id_promo')->on('promo')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
