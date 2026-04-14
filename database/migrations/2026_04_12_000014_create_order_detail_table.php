<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_detail', function (Blueprint $table) {
            $table->id('id_order_detail');
            $table->foreignId('id_order')->constrained('order', 'id_order')->cascadeOnDelete();
            $table->foreignId('id_service')->constrained('service', 'id_service');
            $table->foreignId('id_staff')->nullable()->constrained('staff', 'id_staff')->nullOnDelete();
            $table->time('start');
            $table->time('end');
            $table->decimal('harga_saat_order', 12, 2)->comment('Snapshot harga saat order dibuat');
            $table->unsignedInteger('durasi_menit');
            $table->decimal('subtotal', 12, 2);
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');
            $table->timestamps();

            $table->index('id_order');
            $table->index('id_service');
            $table->index('id_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_detail');
    }
};