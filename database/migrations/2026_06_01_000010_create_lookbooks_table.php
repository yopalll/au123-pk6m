<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookbooks', function (Blueprint $table) {
            $table->id('id_lookbook');
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('deskripsi');
            $table->string('cover_url');
            $table->string('tema');
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookbooks');
    }
};
