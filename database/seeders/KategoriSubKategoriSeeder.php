<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pivot kategori_sub_kategori — 42 baris.
 *
 * Setiap sub_kategori (1..42) terkait dengan 1 kategori parent
 * (urutan sesuai SubKategoriSeeder). Field `urutan` di pivot menyimpan
 * posisi sub_kategori dalam dropdown navbar (1..6 per kategori).
 *
 * Map:
 *   Hair (id=1)         → sub 1..6   (urutan 1..6)
 *   Hair Removal (2)    → sub 7..12  (urutan 1..6)
 *   Massage (3)         → sub 13..18 (urutan 1..6)
 *   Nails (4)           → sub 19..24 (urutan 1..6)
 *   Face (5)            → sub 25..30 (urutan 1..6)
 *   Body (6)            → sub 31..36 (urutan 1..6)
 *   Men's (7)           → sub 37..42 (urutan 1..6)
 */
class KategoriSubKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $payload = [];

        // 7 kategori × 6 sub_kategori (urutan ID 1..42 sequential)
        for ($katId = 1; $katId <= 7; $katId++) {
            for ($pos = 1; $pos <= 6; $pos++) {
                $subId = ($katId - 1) * 6 + $pos;
                $payload[] = [
                    'id_kategori' => $katId,
                    'id_sub_kategori' => $subId,
                    'urutan' => $pos,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('kategori_sub_kategori')->insertOrIgnore($payload);
        $this->command->info('Seeded '.count($payload).' baris pivot kategori_sub_kategori (7 kategori × 6 sub).');
    }
}
