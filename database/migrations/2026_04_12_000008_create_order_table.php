<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order', function (Blueprint $table) {
            $table->id('id_order');
            $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->foreignId('id_salon')->constrained('salon', 'id_salon')->cascadeOnDelete();
            $table->foreignId('id_promo')->nullable()->constrained('promo', 'id_promo')->nullOnDelete();
            $table->string('kode_order', 50)->unique();
            $table->date('date_order');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('diskon_amount', 12, 2)->default(0);
            $table->decimal('total_pembayaran', 12, 2)->default(0);
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_user');
            $table->index('id_salon');
            $table->index('status');
            $table->index('date_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};