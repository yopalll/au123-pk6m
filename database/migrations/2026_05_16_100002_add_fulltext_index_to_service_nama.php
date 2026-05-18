<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * OPT-03 / P1-10: Add FULLTEXT index on service.nama for faster search.
 *
 * The SearchController currently uses LIKE '%q%' which causes a full table scan.
 * On 190K+ service rows this is slow. A FULLTEXT index enables O(log n) search
 * instead of O(n) full scan.
 *
 * Note: InnoDB FULLTEXT is supported in MySQL 5.6+ and MySQL 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add FULLTEXT index on service.nama
        DB::statement('ALTER TABLE `service` ADD FULLTEXT INDEX `service_nama_fulltext` (`nama`)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `service` DROP INDEX `service_nama_fulltext`');
    }
};
