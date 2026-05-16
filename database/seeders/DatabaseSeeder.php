<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed urutan Part 3:
     *   1. kota
     *   2. kategori (7 baris: Hair s/d Men's)
     *   3. sub_kategori (42 baris: treatment dropdown navbar excl See all + Barbers)
     *   4. kategori_sub_kategori (pivot 42 baris)
     *   5. users
     *   6. salon
     *   7. service (FK ke kategori + sub_kategori)
     *   8. salon_kategori (pivot derived dari service)
     *   9. staff
     *  10. salon_images
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('=== Starting VIYGO Database Seeding (Part 3) ===');
        $this->command->newLine();

        $this->command->info('[TRUNCATE] Clearing existing data...');
        DB::table('salon_sub_kategori')->truncate();
        DB::table('salon_kategori')->truncate();
        DB::table('kategori_sub_kategori')->truncate();
        DB::table('salon_images')->truncate();
        DB::table('staff')->truncate();
        DB::table('service')->truncate();
        DB::table('salon')->truncate();
        DB::table('users')->truncate();
        DB::table('sub_kategori')->truncate();
        DB::table('kategori')->truncate();
        DB::table('kota')->truncate();

        $this->call([
            KotaSeeder::class,
            KategoriSeeder::class,
            SubKategoriSeeder::class,
            KategoriSubKategoriSeeder::class,
            UserSeeder::class,
            SalonSeeder::class,
            ServiceSeeder::class,
            SalonKategoriSeeder::class,
            SalonSubKategoriSeeder::class,
            StaffSeeder::class,
            SalonImagesSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->newLine();
        $this->command->info('=== VIYGO Database Seeding (Part 3) Complete! ===');
    }
}
