<?php

function cleanMarkdown($filePath) {
    $content = file_get_contents($filePath);
    
    // Remove Emojis (Regex matches most common emojis)
    $content = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $content); // Emoticons
    $content = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $content); // Misc Symbols and Pictographs
    $content = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $content); // Transport and Map
    $content = preg_replace('/[\x{1F700}-\x{1F77F}]/u', '', $content); // Alchemical Symbols
    $content = preg_replace('/[\x{1F780}-\x{1F7FF}]/u', '', $content); // Geometric Shapes Extended
    $content = preg_replace('/[\x{1F800}-\x{1F8FF}]/u', '', $content); // Supplemental Arrows-C
    $content = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $content); // Supplemental Symbols and Pictographs
    $content = preg_replace('/[\x{1FA00}-\x{1FA6F}]/u', '', $content); // Chess Symbols
    $content = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $content); // Symbols and Pictographs Extended-A
    $content = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $content); // Misc symbols
    $content = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $content); // Dingbats
    $content = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $content); // Variation Selectors
    
    // Clean up extra spaces left by emoji removal
    $content = preg_replace('/ {2,}/', ' ', $content);
    $content = preg_replace('/^ /m', '', $content); // remove leading spaces on lines

    // Replace informal Indonesian with formal academic Indonesian
    $replacements = [
        'pakai' => 'menggunakan',
        'bikin' => 'membuat',
        'udah' => 'sudah',
        'gimana' => 'bagaimana',
        'kalo' => 'jika',
        'karna' => 'karena',
        'bisa' => 'dapat',
        'buat' => 'untuk',
        'aja' => 'saja',
        'biar' => 'agar',
        'nggak' => 'tidak',
        'gak' => 'tidak',
        'enggak' => 'tidak',
        'trus' => 'kemudian',
        'terus' => 'selanjutnya',
        'pas' => 'saat',
        'banget' => 'sekali',
        'cuman' => 'hanya',
        'dapet' => 'mendapat',
        'nanti' => 'kelak',
        'kayak' => 'seperti',
        'keliatan' => 'terlihat',
        'nyari' => 'mencari',
        'gampang' => 'mudah',
        'susah' => 'sulit',
        'cepet' => 'cepat',
        'lama' => 'lambat',
        'bagus' => 'baik',
        'jelek' => 'buruk',
        'gede' => 'besar',
        'kecil' => 'kecil',
        'banyak' => 'berbagai',
        'dikit' => 'sedikit',
        'kasih' => 'memberikan',
        'liat' => 'melihat',
        'tau' => 'mengetahui',
        'males' => 'enggan',
        'emang' => 'memang',
        'sebenernya' => 'sebenarnya',
        'gini' => 'seperti ini',
        'gitu' => 'seperti itu',
        'sampe' => 'hingga',
        'dulu' => 'dahulu',
        'entar' => 'nanti',
        'cepetan' => 'lebih cepat',
        'bener' => 'benar',
        'nambah' => 'menambah',
        'ngurangin' => 'mengurangi',
        'ilang' => 'hilang',
        'dapet' => 'mendapatkan',
        'nyoba' => 'mencoba',
        'nanya' => 'bertanya',
        'jawab' => 'menjawab',
    ];

    foreach ($replacements as $informal => $formal) {
        // Case insensitive whole word replacement
        $content = preg_replace('/\b' . $informal . '\b/i', $formal, $content);
    }

    file_put_contents($filePath, $content);
}

$files = glob('*.md');
foreach ($files as $file) {
    echo "Processing $file...\n";
    cleanMarkdown($file);
}

echo "Done.\n";
?>
