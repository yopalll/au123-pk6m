<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot many-to-many: products <-> product_categories.
 *
 * `products.id_product_category` tetap dipakai sebagai KATEGORI UTAMA (primary type).
 * Tabel pivot ini menampung KATEGORI TAMBAHAN, sehingga 1 produk bisa masuk
 * lebih dari 1 tipe (mis. sebuah serum yang juga tergolong "Travel Size").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_product');
            $table->unsignedBigInteger('id_product_category');
            $table->timestamps();

            $table->unique(['id_product', 'id_product_category']);
            $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
            $table->foreign('id_product_category')->references('id_product_category')->on('product_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
