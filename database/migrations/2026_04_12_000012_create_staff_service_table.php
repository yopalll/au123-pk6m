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
        Schema::create('staff_service', function (Blueprint $table) {
             // Primary Key
        $table->id(); // Sesuai gambar menggunakan nama 'id' bertipe bigint

        // Foreign Keys
       $table->foreignId('id_staff')->constrained('staff', 'id_staff')->cascadeOnDelete();
    $table->foreignId('id_service')->constrained('service', 'id_service')->cascadeOnDelete();
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_service');
    }
};
