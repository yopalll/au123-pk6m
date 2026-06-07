<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `users` MODIFY `role` "
            . "ENUM('customer','salon_owner','admin','admin_store') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }

    public function down(): void
    {
        // Pastikan tidak ada user admin_store sebelum rollback
        DB::statement(
            "ALTER TABLE `users` MODIFY `role` "
            . "ENUM('customer','salon_owner','admin') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }
};
