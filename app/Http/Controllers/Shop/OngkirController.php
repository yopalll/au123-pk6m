<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OngkirController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'destination' => 'required|string',
            'weight' => 'required|integer|min:100',
        ]);

        $cacheKey = 'ongkir_'.md5($request->destination.'_'.$request->weight);

        $result = Cache::remember($cacheKey, config('ongkir.cache_ttl_minutes', 60) * 60, function () use ($request) {
            if (! config('ongkir.api_key')) {
                return $this->mockResult();
            }

            try {
                $response = Http::timeout(config('ongkir.timeout_seconds', 5))
                    ->withHeaders(['x-api-co-id' => config('ongkir.api_key')])
                    ->post(config('ongkir.base_url').'/expedition/cost', [
                        'origin' => config('ongkir.origin_city'),
                        'destination' => $request->destination,
                        'weight' => $request->weight,
                        'courier' => implode(',', config('ongkir.couriers')),
                    ]);

                return $response->json();
            } catch (\Throwable $e) {
                return ['status' => 'error', 'message' => 'Gagal mengecek ongkir. Coba lagi.'];
            }
        });

        return response()->json($result);
    }

    public function cities(Request $request)
    {
        $q = (string) $request->query('q', '');

        if (! config('ongkir.api_key')) {
            return response()->json($this->mockCities($q));
        }

        $cities = Cache::remember('regional_cities_'.md5($q), 86400, function () use ($q) {
            try {
                $response = Http::timeout(config('ongkir.timeout_seconds', 5))
                    ->withHeaders(['x-api-co-id' => config('ongkir.api_key')])
                    ->get(config('ongkir.base_url').'/regional/cities', ['search' => $q]);

                return $response->json('data', []);
            } catch (\Throwable $e) {
                return [];
            }
        });

        return response()->json($cities);
    }

    /**
     * Hasil ongkir tiruan saat API key belum diisi (untuk demo lokal).
     */
    private function mockResult(): array
    {
        return [
            'status' => 'success',
            'mock' => true,
            'data' => [
                ['courier' => 'jne', 'courier_name' => 'JNE', 'services' => [
                    ['service' => 'REG', 'description' => 'Reguler', 'cost' => 18000, 'etd' => '2-3 hari'],
                    ['service' => 'OKE', 'description' => 'Ongkos Ekonomis', 'cost' => 14000, 'etd' => '4-5 hari'],
                    ['service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => 45000, 'etd' => '1 hari'],
                ]],
                ['courier' => 'sicepat', 'courier_name' => 'SiCepat', 'services' => [
                    ['service' => 'BEST', 'description' => 'Best Service', 'cost' => 16000, 'etd' => '2-3 hari'],
                ]],
                ['courier' => 'pos', 'courier_name' => 'Pos Indonesia', 'services' => [
                    ['service' => 'Reguler', 'description' => 'Pos Reguler', 'cost' => 12000, 'etd' => '5-7 hari'],
                ]],
            ],
        ];
    }

    private function mockCities(string $q): array
    {
        $all = ['Jakarta Selatan', 'Jakarta Pusat', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Denpasar', 'Bekasi', 'Depok', 'Tangerang'];

        return collect($all)
            ->filter(fn ($c) => $q === '' || str_contains(strtolower($c), strtolower($q)))
            ->values()
            ->map(fn ($c, $i) => ['id' => $i + 1, 'name' => $c])
            ->all();
    }
}
