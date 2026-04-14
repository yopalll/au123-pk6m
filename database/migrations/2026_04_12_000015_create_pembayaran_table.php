<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_order')->constrained('order', 'id_order')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users', 'id_user');
            $table->string('kode_pembayaran', 50)->unique()->nullable();
            $table->decimal('jumlah', 12, 2);
            $table->enum('metode', [
                'cash',
                'transfer_bank',
                'midtrans',
                'gopay',
                'ovo',
                'dana'
            ]);
            $table->enum('tipe_pembayaran', ['full', 'dp'])->default('full');
            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');
            $table->string('bukti_url')->nullable()->comment('URL foto bukti transfer');
            $table->string('transaction_id')->nullable()->comment('ID dari payment gateway');
            $table->timestamp('waktu_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('id_order');
            $table->index('id_user');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};