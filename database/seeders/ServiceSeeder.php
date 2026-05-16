<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ServiceSeeder V2 — mendukung dua format JSON:
 *
 *   1. BARU (hasil scraper.exe v2): field `id_kategori` ∈ 1..43,
 *      `id_sub_kategori` boleh berupa ID valid atau null.
 *
 *   2. LAMA (sebelum refactor kategori): id_kategori menunjuk ke kategori
 *      lama yang sudah dihapus. Seeder akan SET NULL utk id_kategori yg
 *      tidak ada di tabel `kategori` baru (1..43). Service tetap masuk
 *      DB tapi tanpa kategori — bisa dimapping ulang setelah scrape ulang.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $servicePath = database_path('data/service.json');
        if (!file_exists($servicePath)) {
            $this->command->warn('service.json tidak ditemukan, ServiceSeeder dilewati.');
            return;
        }

        $services = json_decode(file_get_contents($servicePath), true) ?? [];
        if (empty($services)) {
            $this->command->warn('service.json kosong, ServiceSeeder dilewati.');
            return;
        }

        // Whitelist id_kategori valid (kategori 43 baris, sudah di-seed).
        $validKategoriIds = DB::table('kategori')->pluck('id_kategori')->all();
        $validKategoriSet = array_flip($validKategoriIds);

        $validSubIds = DB::table('sub_kategori')->pluck('id_sub_kategori')->all();
        $validSubSet = array_flip($validSubIds);

        $unmapped = 0;
        $chunks = array_chunk($services, 500);

        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $svc) {
                $idKat = $svc['id_kategori'] ?? null;
                if ($idKat !== null && !isset($validKategoriSet[$idKat])) {
                    $idKat = null;
                    $unmapped++;
                }

                $idSub = $svc['id_sub_kategori'] ?? null;
                if ($idSub !== null && !isset($validSubSet[$idSub])) {
                    $idSub = null;
                }

                $rows[] = [
                    'id_service'      => $svc['id_service'],
                    'id_salon'        => $svc['id_salon'],
                    'id_kategori'     => $idKat,
                    'id_sub_kategori' => $idSub,
                    'nama'            => mb_substr($svc['nama'] ?? '', 0, 150),
                    'deskripsi'       => $svc['deskripsi'] ?? null,
                    'durasi'          => $svc['durasi'] ?? 60,
                    'harga'           => $svc['harga'] ?? 0,
                    'status'          => $svc['status'] ?? 'active',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
            DB::table('service')->insert($rows);
        }

        $msg = sprintf('Seeded %d service records', count($services));
        if ($unmapped > 0) {
            $msg .= sprintf(' (%d service: id_kategori invalid → set NULL — re-run scraper utk re-tagging).', $unmapped);
        } else {
            $msg .= '.';
        }
        $this->command->info($msg);
    }
}
