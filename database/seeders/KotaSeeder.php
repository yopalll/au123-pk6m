<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KotaSeeder extends Seeder
{
    /**
     * Seed the kota table from JSON data.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/kota.json'));
        $kotaList = json_decode($json, true);

        // Chunk insert for efficiency (avoid memory issues on large datasets)
        $chunks = array_chunk($kotaList, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($kota) {
                return [
                    'id_kota'   => $kota['id_kota'],
                    'nama_kota' => $kota['nama_kota'],
                    'provinsi'  => $kota['provinsi'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $chunk);

            DB::table('kota')->insert($rows);
        }

        $this->command->info("Seeded " . count($kotaList) . " kota records.");
    }
}
