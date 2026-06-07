<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id('id_product_review');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_product');
            $table->unsignedBigInteger('id_product_order');
            $table->tinyInteger('rating'); // 1-5
            $table->string('judul')->nullable();
            $table->text('komentar')->nullable();
            $table->json('foto_urls')->nullable(); // array of photo URLs
            $table->boolean('is_verified_purchase')->default(true);
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('id_product')->references('id_product')->on('products');
            $table->foreign('id_product_order')->references('id_product_order')->on('product_orders');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
