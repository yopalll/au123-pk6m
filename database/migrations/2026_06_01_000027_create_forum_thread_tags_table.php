<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_thread_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_thread');
            $table->unsignedBigInteger('id_product');
            $table->timestamps();

            $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_thread_tags');
    }
};
