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
        $table->id('id_order_detail');
        $table->foreignId('id_order')
        ->constrained('order', 'id_order')
        ->cascadeOnDelete();
        $table->foreignId('id_service')
        ->constrained('service', 'id_service');
        $table->foreignId('id_staff')
        ->nullable()
        ->constrained('staff', 'id_staff')
        ->nullonDelete(); // Staff yang mengerjakan
        $table->time('start_time');
        $table->time('end_time');
        $table->decimal('harga_at_order', 12, 2); // Simpan harga saat dibeli (untuk history)
        $table->decimal('subtotal', 12, 2); // Harga total untuk service ini (harga_at_order * quantity)
        $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending'); // Status pengerjaan service
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
