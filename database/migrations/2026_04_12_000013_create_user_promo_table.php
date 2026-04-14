<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_promo', function (Blueprint $table) {
            $table->id();
        $table->foreignId('id_user')
        ->constrained('users', 'id_user')
        ->cascadeOnDelete();
        $table->foreignId('id_promo')
        ->constrained('promo', 'id_promo')
        ->cascadeOnDelete();
        $table->boolean('is_used')->default(false);
        $table->timestamp('claimed_at')->nullable();
        $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_promo');
    }
};
