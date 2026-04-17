<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalonImagesSeeder extends Seeder
{
    /**
     * Seed the salon_images table from JSON data.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/salon_images.json'));
        $imagesList = json_decode($json, true);

        $chunks = array_chunk($imagesList, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($img) {
                return [
                    'id_salon_image' => $img['id_salon_image'],
                    'id_salon'       => $img['id_salon'],
                    'image_url'      => $img['image_url'],
                    'is_primary'     => $img['is_primary'],
                    'urutan'         => $img['urutan'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }, $chunk);

            DB::table('salon_images')->insert($rows);
        }

        $this->command->info("Seeded " . count($imagesList) . " salon_images records.");
    }
}
