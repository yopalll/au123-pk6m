<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id('id_reply');
            $table->unsignedBigInteger('id_thread');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('parent_id')->nullable(); // nested reply
            $table->text('konten');
            $table->integer('like_count')->default(0);
            $table->enum('status', ['published', 'hidden', 'deleted'])->default('published');
            $table->timestamps();

            $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('parent_id')->references('id_reply')->on('forum_replies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
    }
};
