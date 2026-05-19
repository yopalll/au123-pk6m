<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitra_applications', function (Blueprint $table) {
            $table->foreignId('id_salon')
                ->nullable()
                ->after('id_kota')
                ->constrained('salon', 'id_salon')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mitra_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_salon');
        });
    }
};
