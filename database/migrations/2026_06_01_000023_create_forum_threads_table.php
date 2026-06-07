<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id('id_thread');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_forum_category');
            $table->string('judul');
            $table->string('slug')->unique();
            $table->longText('konten');
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->enum('status', ['published', 'hidden', 'deleted'])->default('published');
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('id_forum_category')->references('id_forum_category')->on('forum_categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};
