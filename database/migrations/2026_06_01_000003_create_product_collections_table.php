<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collections', function (Blueprint $table) {
            $table->id('id_collection');
            $table->string('nama'); // "Black Tea", "Rose", "Soy"
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('tagline')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collections');
    }
};
