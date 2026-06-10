<?php

namespace App\Console\Commands;

use App\Models\Salon;
use Illuminate\Console\Command;

/**
 * Isi pivot salon_kategori & salon_sub_kategori dari layanan tiap salon.
 *
 * Pivot ini aslinya diisi scraper untuk salon hasil scraping. Salon yang
 * dibuat owner/seed sering belum punya baris pivot, sehingga TIDAK MUNCUL
 * di halaman kategori/sub-kategori (yang memfilter via pivot). Command ini
 * mem-backfill pivot berdasarkan id_kategori/id_sub_kategori dari service.
 */
class SyncSalonCategories extends Command
{
    protected $signature = 'salons:sync-categories {--salon= : Batasi ke 1 id_salon}';

    protected $description = 'Sinkron pivot kategori/sub-kategori salon dari layanannya';

    public function handle(): int
    {
        Salon::query()
            ->when($this->option('salon'), fn ($q) => $q->whereKey($this->option('salon')))
            ->with('services:id_service,id_salon,id_kategori,id_sub_kategori')
            ->chunkById(200, function ($salons) {
                foreach ($salons as $salon) {
                    $katIds = $salon->services->pluck('id_kategori')->filter()->unique()->values();
                    $subIds = $salon->services->pluck('id_sub_kategori')->filter()->unique()->values();

                    // syncWithoutDetaching: tambah yang kurang, tidak hapus data scraper.
                    if ($katIds->isNotEmpty()) {
                        $salon->kategoris()->syncWithoutDetaching($katIds->all());
                    }
                    if ($subIds->isNotEmpty()) {
                        $salon->subKategoris()->syncWithoutDetaching($subIds->all());
                    }
                }
            }, 'id_salon');

        $this->info('Pivot kategori & sub-kategori salon tersinkron dari layanan.');

        return self::SUCCESS;
    }
}
