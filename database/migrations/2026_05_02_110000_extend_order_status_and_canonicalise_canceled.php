<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two enum fixes flagged in README-BUG-AUDIT.md:
 *
 *  - BUG-01: order.status was {pending, success, canceled} — adds `confirmed`
 *            so the Midtrans webhook can transition pending → confirmed after
 *            settlement.
 *  - BUG-05: order_detail.status was {pending, in_progress, completed,
 *            cancelled} (British double-L). Standardises on `canceled`
 *            (single L) to match `order.status`. Also backfills any rows
 *            that already wrote the old spelling.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending','confirmed','success','canceled') NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending','in_progress','completed','canceled','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE `order_detail` SET `status` = 'canceled' WHERE `status` = 'cancelled'");
        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending','in_progress','completed','canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert order_detail spelling first so we don't dangle on the cancel value.
        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending','in_progress','completed','canceled','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE `order_detail` SET `status` = 'cancelled' WHERE `status` = 'canceled'");
        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");

        DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending','success','canceled') NOT NULL");
    }
};
