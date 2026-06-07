<?php

namespace Database\Seeders;

use App\Models\Salon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SalonSlugBackfillSeeder extends Seeder
{
    /**
     * Backfill `slug` for every salon row.
     *
     * Strategy:
     *   1. Compute Str::slug(nama_salon).
     *   2. If the candidate is already taken (in this backfill pass), append "-{id_salon}".
     *   3. saveQuietly() to avoid bumping updated_at.
     *
     * Idempotent: re-running only touches rows whose slug is null OR mismatches the deterministic candidate.
     */
    public function run(): void
    {
        $taken = [];

        Salon::withTrashed()
            ->select('id_salon', 'nama_salon', 'slug')
            ->orderBy('id_salon')
            ->chunkById(500, function ($salons) use (&$taken) {
                foreach ($salons as $salon) {
                    $base = Str::slug($salon->nama_salon ?: 'salon');
                    $base = $base !== '' ? $base : 'salon';

                    $candidate = $base;
                    if (isset($taken[$candidate])) {
                        $candidate = $base.'-'.$salon->id_salon;
                    }

                    $taken[$candidate] = true;

                    if ($salon->slug !== $candidate) {
                        $salon->slug = $candidate;
                        $salon->saveQuietly();
                    }
                }
            }, 'id_salon');

        $this->command?->info('Salon slug backfill complete. '.count($taken).' unique slugs assigned.');
    }
}
