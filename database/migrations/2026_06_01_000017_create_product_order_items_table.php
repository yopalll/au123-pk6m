<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id('id_item');
            $table->unsignedBigInteger('id_product_order');
            $table->unsignedBigInteger('id_product');
            $table->string('nama_produk'); // snapshot saat beli
            $table->integer('qty');
            $table->decimal('harga_satuan', 10, 2);
            $table->integer('berat_gram'); // snapshot berat saat beli
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('id_product_order')->references('id_product_order')->on('product_orders')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_items');
    }
};
