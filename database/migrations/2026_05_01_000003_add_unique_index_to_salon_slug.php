<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run after SalonSlugBackfillSeeder so all rows have non-null
     * unique slugs before the unique index is enforced.
     */
    public function up(): void
    {
        Schema::table('salon', function (Blueprint $table) {
            // Drop the non-unique index added in the previous migration
            // before promoting the column to a unique key.
            $table->dropIndex(['slug']);
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('salon', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });
    }
};
