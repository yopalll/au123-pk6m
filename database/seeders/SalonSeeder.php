<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalonSeeder extends Seeder
{
    /**
     * Seed the salon table from JSON data.
     * id_user diambil langsung dari salon.id_user (di-set oleh scraper Go,
     * link ke owner user yg generated bersamaan).
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/salon.json'));
        $salons = json_decode($json, true) ?? [];

        if (empty($salons)) {
            $this->command->warn('salon.json is empty, skipping SalonSeeder.');

            return;
        }

        $chunks = array_chunk($salons, 50);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($salon) {
                return [
                    'id_salon' => $salon['id_salon'],
                    'id_user' => $salon['id_user'], // owner — di-set oleh scraper
                    'id_kota' => $salon['id_kota'],
                    'nama_salon' => $salon['nama_salon'],
                    'alamat' => $salon['alamat'],
                    'deskripsi' => $salon['deskripsi'],
                    'phone_number' => $salon['phone_number'],
                    'opening_time' => $salon['opening_time'],
                    'closing_time' => $salon['closing_time'],
                    'image_url' => $salon['image_url'],
                    'maps_url' => $salon['maps_url'],
                    'latitude' => $salon['latitude'],
                    'longitude' => $salon['longitude'],
                    'rating' => $salon['rating'],
                    'total_review' => $salon['total_review'],
                    'status' => $salon['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $chunk);

            DB::table('salon')->insert($rows);
        }

        $this->command->info('Seeded '.count($salons).' salon records.');
    }
}
