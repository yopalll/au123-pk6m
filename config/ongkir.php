<?php

return [
    // Ongkir flat tanpa API: gudang VIYGO tersebar di hampir seluruh provinsi,
    // jadi tarif kirim seragam.
    'flat_cost' => (int) env('ONGKIR_FLAT_COST', 10000),              // Rp 10.000 per pesanan
    'free_ongkir_threshold' => (int) env('ONGKIR_FREE_THRESHOLD', 100000), // gratis jika subtotal >= Rp 100.000

    // Label pengiriman yang ditampilkan & disimpan di pesanan.
    'courier' => 'VIYGO',
    'service' => 'Reguler',
    'etd' => '2-4 hari',
];
