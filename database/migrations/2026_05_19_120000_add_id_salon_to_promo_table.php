<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add id_salon FK to promo so salon owners can create their own discounts.
     *
     * NULL  → platform-wide promo created by admin (existing behavior)
     * set N → salon-specific promo, only valid when booking salon N
     */
    public function up(): void
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->foreignId('id_salon')
                ->nullable()
                ->after('id_promo')
                ->constrained('salon', 'id_salon')
                ->cascadeOnDelete();

            $table->index('id_salon');
        });
    }

    public function down(): void
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_salon');
        });
    }
};
