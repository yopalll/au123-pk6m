<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    /**
     * Seed the kategori table from JSON data.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/kategori.json'));
        $kategoriList = json_decode($json, true);

        foreach ($kategoriList as $kategori) {
            DB::table('kategori')->insert([
                'id_kategori' => $kategori['id_kategori'],
                'name'        => $kategori['name'],
                'deskripsi'   => $kategori['deskripsi'],
                'slug'        => $kategori['slug'],
                'icon_url'    => $kategori['icon_url'],
                'is_active'   => $kategori['is_active'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info("Seeded " . count($kategoriList) . " kategori records.");
    }
}
