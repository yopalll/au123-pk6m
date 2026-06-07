<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SubKategoriSeeder Part 3 — 42 baris (NO dedup, slug pakai suffix grup).
 *
 * Sumber: dropdown navbar Treatwell, 49 items dikurangi 7 yang skip:
 *   - 6 "See all hair/HR/massage/nails/face/body treatments" → handle by route
 *   - 1 "Barbers" di Mens → handle by query khusus
 * Sisa = 42 sub_kategori (positions di dropdown).
 *
 * "Men's Haircut" muncul di dropdown Hair (id=6) dan Mens (id=37) dgn slug
 * berbeda. "Men's Waxing" muncul di Hair Removal (id=11) dan Mens (id=42).
 * URL berbeda — pengguna bisa land di halaman terpisah dgn sumber salon
 * yg berbeda kalau scraper menemukan listing yg berbeda.
 *
 * IMPORTANT: ID 1..42 dipakai by KategoriSubKategoriSeeder + scraper.go.
 * Kalau urutan diubah, sinkronkan!
 */
class SubKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [];
        foreach (self::dataset() as $i => $row) {
            $rows[] = [
                'id_sub_kategori' => $i + 1,
                'name' => $row[0],
                'slug' => $row[1],
                'treatwell_slug' => $row[2],
                'deskripsi' => null,
                'icon_url' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sub_kategori')->upsert(
            $rows,
            ['slug'],
            ['name', 'treatwell_slug', 'is_active', 'updated_at']
        );

        $this->command->info('Seeded '.count($rows).' sub_kategori (excluding 6 "See all" links + 1 "Barbers" — handled by query).');
    }

    /**
     * 42 sub_kategori. Format: [name, slug, treatwell_slug].
     * Slug "Men's Haircut" = mens-haircut-hair (di Hair) vs mens-haircut-mens (di Mens).
     * Sinkron dgn scraper.go (subKategoriRegistry).
     */
    public static function dataset(): array
    {
        return [
            // ── HAIR (id 1..6) ────────────────────────────────────
            ["Ladies' Haircuts",                      'ladies-haircuts',                'ladies-haircuts-1'],
            ['Blow Dry',                              'blow-dry',                       'blow-dry'],
            ["Ladies' Hair Colouring & Highlights",   'ladies-hair-colouring-highlights', 'hair-colouring'],
            ["Ladies' Brazilian Blow Dry",            'ladies-brazilian-blow-dry',      'ladies-brazilian-blow-dry'],
            ['Balayage & Ombre',                      'balayage-ombre',                 'balayage'],
            ["Men's Haircut",                         'mens-haircut-hair',              'men-s-haircut'],

            // ── HAIR REMOVAL (id 7..12) ───────────────────────────
            ['Facial Threading',     'facial-threading',         'facial-threading'],
            ["Ladies' Waxing",       'ladies-waxing',            'ladies-waxing'],
            ['Sugaring',             'sugaring',                 'sugaring'],
            ['Hollywood Waxing',     'hollywood-waxing',         'hollywood-waxing'],
            ["Men's Waxing",         'mens-waxing-hair-removal', 'men-s-waxing'],
            ["Ladies' Leg Waxing",   'ladies-leg-waxing',        'ladies-leg-waxing'],

            // ── MASSAGE (id 13..18) ───────────────────────────────
            ['Deep Tissue Massage',  'deep-tissue-massage',  'deep-tissue-massage'],
            ['Swedish Massage',      'swedish-massage',      'swedish-massage'],
            ['Therapeutic Massage',  'therapeutic-massage',  'therapeutic-massage'],
            ['Thai Massage',         'thai-massage',         'thai-massage'],
            ['Aromatherapy Massage', 'aromatherapy-massage', 'aromatherapy-massage'],
            ['Hot Stone Massage',    'hot-stone-massage',    'hot-stone-massage'],

            // ── NAILS (id 19..24) ─────────────────────────────────
            ['Pedicure',                                'pedicure',                          'pedicure'],
            ['Manicure',                                'manicure',                          'manicure'],
            ['Nail or Gel Polish Removal',              'nail-gel-polish-removal',           'nail-or-gel-polish-removal'],
            ['Gel Nails Manicure',                      'gel-nails-manicure',                'gel-nails-manicure'],
            ['Gel Nails Pedicure',                      'gel-nails-pedicure',                'gel-nails-pedicure'],
            ['Acrylic, Hard Gel & Nail Extensions',     'acrylic-hard-gel-nail-extensions',  'hard-gel-extensions-overlays'],

            // ── FACE (id 25..30) ──────────────────────────────────
            ['Classic Facials',              'classic-facials',           'classic-facials'],
            ['Eyelash Extensions',           'eyelash-extensions',        'eyelash-extensions'],
            ['Eyebrow and Eyelash Tinting',  'eyebrow-eyelash-tinting',   'eyebrow-eyelash-tinting'],
            ['Eyebrow Threading',            'eyebrow-threading',         'eyebrow-threading'],
            ['Eyebrow Waxing',               'eyebrow-waxing',            'eyebrow-waxing'],
            ['Definition Brows',             'definition-brows',          'brow-definition'],

            // ── BODY (id 31..36) ──────────────────────────────────
            ['Spray Tanning and Sunless Tanning',  'spray-tanning-sunless-tanning', 'spray-tanning-and-sunless-tanning'],
            ['Body Exfoliation Treatments',        'body-exfoliation-treatments',   'body-exfoliation-treatments'],
            ['Body Wraps',                         'body-wraps',                    'body-wraps'],
            ['Colonic Hydrotherapy',               'colonic-hydrotherapy',          'colonic-hydrotherapy'],
            ['Cryolipolysis',                      'cryolipolysis',                 'cryolipolysis'],
            ['Cellulite Treatments',               'cellulite-treatments',          'cellulite-treatments'],

            // ── MEN'S (id 37..42) ─────────────────────────────────
            // (NOTE: "Barbers" tidak masuk DB — handle di /kategori/mens?filter=barbers)
            ["Men's Haircut",              'mens-haircut-mens',           'men-s-haircut'],
            ['Beard Trims and Shaves',     'beard-trims-shaves',          'beard-trimming'],
            ["Men's Hair Colouring",       'mens-hair-colouring',         'men-s-hair-colouring'],
            ["Men's Brazilian Blow Dry",   'mens-brazilian-blow-dry',     'men-s-brazilian-blow-dry'],
            ["Men's Facials",              'mens-facials',                'men-s-facials'],
            ["Men's Waxing",               'mens-waxing-mens',            'men-s-waxing'],
        ];
    }
}
