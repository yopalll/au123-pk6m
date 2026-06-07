<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * KategoriSeeder Part 3 — 7 baris kategori utama (level header navbar).
 *
 * Sumber: navbar Treatwell.co.uk top-level. Field `treatwell_slug`
 * dipakai scraper Go untuk membangun URL listing per grup
 * (mis. https://www.treatwell.co.uk/places/treatment-group-{treatwell_slug}/...).
 *
 * IMPORTANT: ID 1..7 dipakai by scraper.go (kategoriRegistry) — kalau
 * urutan diubah, scraper HARUS disesuaikan.
 */
class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['id_kategori' => 1, 'name' => 'Hair',         'slug' => 'hair',         'treatwell_slug' => 'hair',           'urutan' => 1, 'deskripsi' => 'Layanan perawatan rambut: potong, warna, blow dry, smoothing.'],
            ['id_kategori' => 2, 'name' => 'Hair Removal', 'slug' => 'hair-removal', 'treatwell_slug' => 'hair-removal',   'urutan' => 2, 'deskripsi' => 'Layanan penghilangan bulu: waxing, threading, sugaring.'],
            ['id_kategori' => 3, 'name' => 'Massage',      'slug' => 'massage',      'treatwell_slug' => 'massage',        'urutan' => 3, 'deskripsi' => 'Pijat relaksasi & terapi: deep tissue, swedish, thai, hot stone.'],
            ['id_kategori' => 4, 'name' => 'Nails',        'slug' => 'nails',        'treatwell_slug' => 'nails',          'urutan' => 4, 'deskripsi' => 'Perawatan kuku: manicure, pedicure, gel, akrilik.'],
            ['id_kategori' => 5, 'name' => 'Face',         'slug' => 'face',         'treatwell_slug' => 'face-beauty',    'urutan' => 5, 'deskripsi' => 'Perawatan wajah: facial, eyelash, eyebrow.'],
            ['id_kategori' => 6, 'name' => 'Body',         'slug' => 'body',         'treatwell_slug' => 'body-treatments', 'urutan' => 6, 'deskripsi' => 'Perawatan tubuh: spray tan, body scrub, slimming.'],
            ['id_kategori' => 7, 'name' => "Men's",        'slug' => 'mens',         'treatwell_slug' => 'mens-grooming',  'urutan' => 7, 'deskripsi' => 'Layanan khusus pria: barber, beard, men\'s grooming.'],
        ];

        $payload = array_map(fn ($r) => $r + [
            'icon_url' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        DB::table('kategori')->upsert(
            $payload,
            ['slug'],
            ['name', 'treatwell_slug', 'deskripsi', 'urutan', 'is_active', 'updated_at']
        );

        $this->command->info('Seeded 7 kategori utama: Hair, Hair Removal, Massage, Nails, Face, Body, Men\'s.');
    }
}
