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
        Schema::create('staff_schedule', function (Blueprint $table) {
              // Primary Key
        $table->id('id_schedule');

        // Foreign Key
        $table->foreignId('id_staff')->constrained('staff', 'id_staff')->cascadeOnDelete();

        // Kolom Data
        $table->enum('hari', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        $table->time('start_time');
        $table->time('end_time');
        $table->boolean('is_available')->default(true);

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_schedule');
    }
};
