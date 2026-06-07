<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id('id_forum_category');
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_categories');
    }
};
