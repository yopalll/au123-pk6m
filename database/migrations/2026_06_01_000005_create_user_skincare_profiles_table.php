<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_skincare_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->unique();
            $table->enum('skin_type', ['oily', 'dry', 'combination', 'sensitive', 'normal']);
            $table->string('skin_concerns'); // comma-separated
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skincare_profiles');
    }
};
