<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->unique();
            $table->integer('saldo')->default(0);
            $table->integer('total_earned')->default(0);
            $table->integer('total_spent')->default(0);
            $table->enum('tier', ['starter', 'bronze', 'silver', 'gold'])->default('starter');
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_points');
    }
};
