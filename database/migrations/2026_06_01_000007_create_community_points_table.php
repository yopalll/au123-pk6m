<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->unique();
            $table->integer('total_points')->default(0);
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_points');
    }
};
