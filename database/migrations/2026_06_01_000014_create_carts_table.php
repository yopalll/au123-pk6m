<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id('id_cart');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_product');
            $table->integer('qty');
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
