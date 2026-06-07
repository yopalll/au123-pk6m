<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookbook_slides', function (Blueprint $table) {
            $table->id('id_slide');
            $table->unsignedBigInteger('id_lookbook');
            $table->string('judul')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('image_url');
            $table->text('tips')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('id_lookbook')->references('id_lookbook')->on('lookbooks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookbook_slides');
    }
};
