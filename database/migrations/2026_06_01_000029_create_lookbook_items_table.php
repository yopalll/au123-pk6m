<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookbook_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_slide');
            $table->unsignedBigInteger('id_product');
            $table->decimal('position_x', 5, 2)->default(0); // posisi tag di gambar (%)
            $table->decimal('position_y', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_slide')->references('id_slide')->on('lookbook_slides')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookbook_items');
    }
};
