<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_thread');
            $table->timestamp('created_at')->nullable();

            $table->unique(['id_user', 'id_thread']);
            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
            $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_bookmarks');
    }
};
