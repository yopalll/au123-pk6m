<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters! Tables with foreign key dependencies must be
     * seeded after their parent tables.
     *
     * Execution order:
     *   1. kota       (no FK)
     *   2. kategori   (no FK)
     *   3. users      (no FK)
     *   4. salon      (FK → users, kota)
     *   5. service    (FK → salon, kategori)
     *   6. staff      (FK → salon)
     *   7. salon_images (FK → salon)
     */
    public function run(): void
    {
        // Disable FK checks during seeding to avoid constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('=== Starting VIYGO Database Seeding ===');
        $this->command->newLine();

        // Truncate in reverse dependency order (safe to re-run)
        $this->command->info('[TRUNCATE] Clearing existing data...');
        DB::table('salon_images')->truncate();
        DB::table('staff')->truncate();
        DB::table('service')->truncate();
        DB::table('salon')->truncate();
        DB::table('users')->truncate();
        DB::table('kategori')->truncate();
        DB::table('kota')->truncate();

        $this->call([
            KotaSeeder::class,
            KategoriSeeder::class,
            UserSeeder::class,
            SalonSeeder::class,
            ServiceSeeder::class,
            StaffSeeder::class,
            SalonImagesSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->newLine();
        $this->command->info('=== VIYGO Database Seeding Complete! ===');
    }
}
