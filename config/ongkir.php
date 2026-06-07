<?php

return [
    'api_key' => env('API_CO_ID_KEY'),
    'base_url' => 'https://api.co.id',
    'origin_city' => env('ONGKIR_ORIGIN_CITY', 'Jakarta Selatan'),
    'free_ongkir_threshold' => 500000, // Rp 500.000
    'couriers' => ['jne', 'jnt', 'sicepat', 'pos'],
    'timeout_seconds' => 5,
    'cache_ttl_minutes' => 60, // cache hasil cek ongkir selama 1 jam
];
