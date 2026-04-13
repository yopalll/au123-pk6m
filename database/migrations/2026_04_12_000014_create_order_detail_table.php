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
        Schema::create('order_detail', function (Blueprint $table) {
            $table->id();
        $table->foreignId('id_order')->constrained('order', 'id_order')->cascadeOnDelete();
        $table->foreignId('id_service')->constrained('service', 'id_service');
        $table->foreignId('id_staff')->nullable()->constrained('staff', 'id_staff'); // Staff yang mengerjakan
        $table->decimal('harga_at_order', 12, 2); // Simpan harga saat dibeli (untuk history)
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_detail');
    }
};
