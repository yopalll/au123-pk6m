<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->unsignedBigInteger('id_product_order');
            $table->unsignedBigInteger('id_user');
            $table->string('midtrans_order_id')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('snap_token')->nullable();
            $table->string('metode')->nullable();
            $table->decimal('jumlah', 10, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'refund'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('id_product_order')->references('id_product_order')->on('product_orders')->cascadeOnDelete();
            $table->foreign('id_user')->references('id_user')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pembayaran');
    }
};
