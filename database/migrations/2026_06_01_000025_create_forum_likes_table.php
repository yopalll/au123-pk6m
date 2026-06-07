<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('likeable_type'); // 'forum_thread' atau 'forum_reply'
            $table->unsignedBigInteger('likeable_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['id_user', 'likeable_type', 'likeable_id']);
            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_likes');
    }
};
