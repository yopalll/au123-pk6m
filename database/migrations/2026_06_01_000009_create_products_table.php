<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('id_product');
            $table->unsignedBigInteger('id_product_category');
            $table->unsignedBigInteger('id_collection')->nullable();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi');
            $table->text('key_ingredients')->nullable();
            $table->longText('full_ingredients')->nullable();
            $table->text('cara_pemakaian')->nullable();
            $table->decimal('harga', 10, 2);
            $table->decimal('harga_diskon', 10, 2)->nullable();
            $table->integer('stok')->default(0);
            $table->integer('berat_gram'); // untuk kalkulasi ongkir
            $table->integer('volume_ml')->nullable();
            $table->string('skin_type')->default('all'); // 'all','oily','dry','combination','sensitive','normal'
            $table->string('skin_concern')->nullable(); // comma-separated: 'dehydration,dullness,acne'
            $table->string('brand')->default('Fresh');
            $table->string('badge')->nullable(); // 'bestseller','new','eco','travel_size'
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_review')->default(0);
            $table->integer('total_sold')->default(0);
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->string('fresh_product_id')->nullable(); // ID dari fresh.com
            $table->string('fresh_url')->nullable(); // URL sumber di fresh.com
            $table->timestamps();

            $table->foreign('id_product_category')->references('id_product_category')->on('product_categories');
            $table->foreign('id_collection')->references('id_collection')->on('product_collections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
