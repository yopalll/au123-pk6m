<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            // Midtrans transaction id (their internal id, not VYG- order code)
            $table->string('id_transaksi')->nullable()->after('metode_pembayaran');

            // Snap token cached for the duration of the pop-up (~5 mins)
            $table->string('snap_token')->nullable()->after('id_transaksi');

            // Raw notification payload — handy for debugging / refund reconciliation
            $table->json('raw_response')->nullable()->after('snap_token');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(['id_transaksi', 'snap_token', 'raw_response']);
        });
    }
};
