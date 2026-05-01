<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the service table from JSON data.
     * Uses chunked inserts for 1900+ records.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/service.json'));
        $services = json_decode($json, true) ?? [];

        if (empty($services)) {
            $this->command->warn('service.json is empty, skipping ServiceSeeder.');
            return;
        }

        $chunks = array_chunk($services, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($service) {
                return [
                    'id_service'  => $service['id_service'],
                    'id_salon'    => $service['id_salon'],
                    'id_kategori' => $service['id_kategori'],
                    'nama'        => mb_substr($service['nama'], 0, 150),
                    'deskripsi'   => $service['deskripsi'],
                    'durasi'      => $service['durasi'],
                    'harga'       => $service['harga'],
                    'status'      => $service['status'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }, $chunk);

            DB::table('service')->insert($rows);
        }

        $this->command->info("Seeded " . count($services) . " service records.");
    }
}
