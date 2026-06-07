<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exclusive_contents', function (Blueprint $table) {
            $table->id('id_content');
            $table->string('judul');
            $table->string('slug')->unique();
            $table->enum('tipe', ['article', 'video', 'tip']);
            $table->longText('konten')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->enum('min_tier', ['bronze', 'silver', 'gold']);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusive_contents');
    }
};
