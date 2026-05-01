<?php

$basePath = __DIR__ . '/..';

$files = [
    'kota'         => $basePath . '/data/kota.json',
    'kategori'     => $basePath . '/data/kategori.json',
    'salon'        => $basePath . '/data/salon.json',
    'service'      => $basePath . '/data/service.json',
    'staff'        => $basePath . '/data/staff.json',
    'salon_images' => $basePath . '/data/salon_images.json',
];

$requiredKeys = [
    'kota'         => ['id_kota', 'nama_kota', 'provinsi'],
    'kategori'     => ['id_kategori', 'name', 'deskripsi', 'slug', 'icon_url', 'is_active'],
    'salon'        => ['id_salon', 'id_user', 'id_kota', 'nama_salon', 'alamat', 'deskripsi',
                       'phone_number', 'opening_time', 'closing_time', 'image_url', 'maps_url',
                       'latitude', 'longitude', 'rating', 'total_review', 'status'],
    'service'      => ['id_service', 'id_salon', 'id_kategori', 'nama', 'deskripsi', 'durasi', 'harga', 'status'],
    'staff'        => ['id_staff', 'id_salon', 'name', 'profile_url', 'status'],
    'salon_images' => ['id_salon_image', 'id_salon', 'image_url', 'is_primary', 'urutan'],
];

$allOk = true;
echo PHP_EOL . "=== JSON Validation ===" . PHP_EOL . PHP_EOL;

foreach ($files as $name => $path) {
    $json = @file_get_contents($path);
    if ($json === false) {
        echo "[ERROR] $name: file tidak ditemukan" . PHP_EOL;
        $allOk = false;
        continue;
    }

    $data = json_decode($json, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo "[ERROR] $name: JSON tidak valid - " . json_last_error_msg() . PHP_EOL;
        $allOk = false;
        continue;
    }

    if (empty($data)) {
        echo "[WARN]  $name: kosong (0 records)" . PHP_EOL;
        continue;
    }

    $errors = [];
    $dupIds = [];
    $idKey  = 'id_' . ($name === 'salon_images' ? 'salon_image' : ($name === 'kategori' ? 'kategori' : ($name === 'salon' ? 'salon' : ($name === 'service' ? 'service' : ($name === 'staff' ? 'staff' : 'kota')))));

    foreach ($data as $i => $row) {
        // Cek atribut wajib
        foreach ($requiredKeys[$name] as $key) {
            if (!array_key_exists($key, $row)) {
                $errors[] = "row[$i] missing key '$key'";
                if (count($errors) >= 5) break 2;
            }
        }
        // Cek duplikat ID
        if (isset($row[$idKey])) {
            $id = $row[$idKey];
            if (isset($dupIds[$id])) {
                $errors[] = "duplicate $idKey = $id (row $i)";
                if (count($errors) >= 5) break;
            }
            $dupIds[$id] = true;
        }
    }

    if (!empty($errors)) {
        echo "[ERROR] $name (" . count($data) . " records): " . implode(' | ', $errors) . PHP_EOL;
        $allOk = false;
    } else {
        echo "[OK]    $name: " . count($data) . " records - semua atribut lengkap, tidak ada duplikat ID" . PHP_EOL;
    }
}

echo PHP_EOL;
if ($allOk) {
    echo "=== Semua JSON VALID - Aman untuk di-seed ===" . PHP_EOL;
} else {
    echo "=== Ada ERROR - Perbaiki sebelum seed ===" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;
