<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo', function (Blueprint $table) {
            $table->id('id_promo');
            $table->string('nama_promo', 150);
            $table->text('deskripsi_promo')->nullable();
            $table->decimal('diskon', 5, 2)->comment('Persentase diskon (%)');
            $table->decimal('diskon_max', 12, 2)->nullable()->comment('Maksimum nominal diskon');
            $table->decimal('min_transaksi', 12, 2)->default(0)->comment('Minimum total transaksi untuk pakai promo');
            $table->enum('tipe_promo', ['percentage', 'fixed'])->default('percentage');
            $table->string('kode_promo', 50)->unique();
            $table->timestamp('time_start');
            $table->timestamp('time_expired');
            $table->unsignedInteger('stock')->default(0)->comment('0 = unlimited');
            $table->unsignedInteger('used_counter')->default(0);
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('kode_promo');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo');
    }
};
