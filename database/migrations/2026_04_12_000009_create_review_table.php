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
        Schema::create('review', function (Blueprint $table) {
              // Primary Key
        $table->id('id_review'); 

        // Foreign Keys (Tipe BigInt)
        $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
        $table->foreignId('id_salon')->constrained('salon', 'id_salon')->cascadeOnDelete();
        $table->foreignId('id_order')->constrained('order', 'id_order')->cascadeOnDelete();

        // Kolom Data
        $table->tinyInteger('rating'); // tinyint sesuai gambar
        $table->text('komentar');      // text sesuai gambar
        $table->boolean('is_visible')->default(true); // bool sesuai 
        
        $table->timestamps(); // Opsional: untuk record waktu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review');
    }
};
