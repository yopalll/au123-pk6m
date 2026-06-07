<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('badge_slug'); // 'skincare_guru','top_reviewer','eco_warrior','rising_star'
            $table->dateTime('earned_at');
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};
