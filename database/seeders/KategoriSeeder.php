<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    /**
     * Seed the kategori table from JSON data.
     * Uses upsert to handle duplicate slugs gracefully.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/kategori.json'));
        $kategoriList = json_decode($json, true);

        // Deduplicate by slug (keep first occurrence)
        $seen = [];
        $unique = [];
        foreach ($kategoriList as $k) {
            if (!isset($seen[$k['slug']])) {
                $seen[$k['slug']] = true;
                $unique[] = $k;
            }
        }

        $chunks = array_chunk($unique, 100);
        foreach ($chunks as $chunk) {
            $rows = array_map(fn($k) => [
                'id_kategori' => $k['id_kategori'],
                'name'        => $k['name'],
                'deskripsi'   => $k['deskripsi'],
                'slug'        => $k['slug'],
                'icon_url'    => $k['icon_url'],
                'is_active'   => $k['is_active'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ], $chunk);

            DB::table('kategori')->upsert(
                $rows,
                ['slug'],                                  // unique key
                ['name', 'deskripsi', 'updated_at']        // update if exists
            );
        }

        $this->command->info("Seeded " . count($unique) . " kategori records (" . (count($kategoriList) - count($unique)) . " duplicates skipped).");
    }
}
