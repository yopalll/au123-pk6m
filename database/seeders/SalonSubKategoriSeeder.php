<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pivot M:N salon ↔ sub_kategori.
 *
 * Diisi LANGSUNG dari salon_sub_kategori.json hasil scraper Go.
 * Scraper men-tag salon ke sub_kategori berdasarkan URL listing yg di-visit:
 *   - URL /places/treatment-blow-dry/in-london-uk/ → tag salon ke sub #2 Blow Dry
 *
 * Fallback: kalau JSON kosong/tidak ada, derive dari distinct(service.id_sub_kategori
 * per id_salon) — supaya pivot tetap terisi via service tagging.
 */
class SalonSubKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/salon_sub_kategori.json');
        $rowsFromJson = file_exists($path)
            ? json_decode(file_get_contents($path), true) ?? []
            : [];

        if (!empty($rowsFromJson)) {
            $now = now();
            $payload = array_map(fn ($r) => [
                'id_salon'        => $r['id_salon'],
                'id_sub_kategori' => $r['id_sub_kategori'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ], $rowsFromJson);

            foreach (array_chunk($payload, 1000) as $chunk) {
                DB::table('salon_sub_kategori')->insertOrIgnore($chunk);
            }
            $this->command->info('Seeded ' . count($payload) . ' baris pivot salon_sub_kategori dari JSON scraper.');
            return;
        }

        // Fallback: derive dari service
        $rows = DB::table('service')
            ->select('id_salon', 'id_sub_kategori')
            ->whereNotNull('id_sub_kategori')
            ->where('status', 'active')
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            $this->command->warn('salon_sub_kategori.json kosong & service tidak punya id_sub_kategori → pivot dilewati.');
            return;
        }

        $now = now();
        $payload = $rows->map(fn ($r) => [
            'id_salon'        => $r->id_salon,
            'id_sub_kategori' => $r->id_sub_kategori,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->all();

        foreach (array_chunk($payload, 1000) as $chunk) {
            DB::table('salon_sub_kategori')->insertOrIgnore($chunk);
        }
        $this->command->info('Derived ' . count($payload) . ' baris salon_sub_kategori dari distinct(service.id_sub_kategori).');
    }
}
