<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')
                ->constrained('users', 'id_user')
                ->cascadeOnDelete();
            $table->foreignId('id_salon')
                ->constrained('salon', 'id_salon')
                ->cascadeOnDelete();
            $table->timestamps();

            // A user can only favourite a given salon once.
            $table->unique(['id_user', 'id_salon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favourites');
    }
};
