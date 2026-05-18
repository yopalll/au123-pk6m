<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OPT-07: Add composite indexes to the salon table for public listing queries.
 *
 * Public listing sorts by 'rating DESC' and 'total_review DESC' while filtering
 * on status='active'. Single-column indexes on (status) and (rating) are less
 * efficient than composite indexes for this query pattern.
 *
 * OPT-08: Add composite index to the order table for CompleteBookings query.
 *
 * CompleteBookings::handle() queries: WHERE status='confirmed' AND date_order < today.
 * Without a composite index, this is a full table scan on large datasets.
 */
return new class extends Migration
{
    public function up(): void
    {
        // OPT-07: Composite indexes for salon listing sort/filter queries
        Schema::table('salon', function (Blueprint $table) {
            $table->index(['status', 'rating'], 'salon_status_rating_idx');
            $table->index(['status', 'total_review'], 'salon_status_review_idx');
        });

        // OPT-08: Composite index for CompleteBookings command
        Schema::table('order', function (Blueprint $table) {
            $table->index(['status', 'date_order'], 'order_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('salon', function (Blueprint $table) {
            $table->dropIndex('salon_status_rating_idx');
            $table->dropIndex('salon_status_review_idx');
        });

        Schema::table('order', function (Blueprint $table) {
            $table->dropIndex('order_status_date_idx');
        });
    }
};
