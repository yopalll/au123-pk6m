<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pivot M:N salon ↔ kategori utama.
 *
 * Diisi LANGSUNG dari salon_kategori.json hasil scraper Go (registry-driven).
 * Scraper men-tag salon ke kategori berdasarkan URL listing yg di-visit:
 *   - URL /places/treatment-group-hair/in-london-uk/ → tag salon ke kategori #1 Hair
 *
 * Fallback: kalau JSON kosong/tidak ada, derive dari distinct(service.id_kategori
 * per id_salon) — supaya pivot tetap terisi untuk salon yg di-scrape via mode lama.
 */
class SalonKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/salon_kategori.json');
        $rowsFromJson = file_exists($path)
            ? json_decode(file_get_contents($path), true) ?? []
            : [];

        if (! empty($rowsFromJson)) {
            $now = now();
            $payload = array_map(fn ($r) => [
                'id_salon' => $r['id_salon'],
                'id_kategori' => $r['id_kategori'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $rowsFromJson);

            foreach (array_chunk($payload, 1000) as $chunk) {
                DB::table('salon_kategori')->insertOrIgnore($chunk);
            }
            $this->command->info('Seeded '.count($payload).' baris pivot salon_kategori dari JSON scraper.');

            return;
        }

        // Fallback: derive dari service
        $rows = DB::table('service')
            ->select('id_salon', 'id_kategori')
            ->whereNotNull('id_kategori')
            ->where('status', 'active')
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            $this->command->warn('salon_kategori.json kosong & service tidak punya id_kategori → pivot dilewati.');

            return;
        }

        $now = now();
        $payload = $rows->map(fn ($r) => [
            'id_salon' => $r->id_salon,
            'id_kategori' => $r->id_kategori,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($payload, 1000) as $chunk) {
            DB::table('salon_kategori')->insertOrIgnore($chunk);
        }
        $this->command->info('Derived '.count($payload).' baris salon_kategori dari distinct(service.id_kategori).');
    }
}
