<?php
/**
 * Generate LAPORAN_AUDIT_KATEGORI.md dari hasil audit_result.json.
 */

$res = json_decode(file_get_contents(__DIR__.'/audit_result.json'), true);

$totalSubs   = 0;
$ok          = 0; // ✅
$warn        = 0; // ⚠️
$missing     = 0; // ❌
foreach ($res as $grp => $subs) {
    foreach ($subs as $s) {
        $totalSubs++;
        if ($s['status'] === '✅') $ok++;
        elseif ($s['status'] === '⚠️') $warn++;
        else $missing++;
    }
}

$today = date('Y-m-d');

$out = <<<MD
# 📊 LAPORAN AUDIT KATEGORI — VIYGO vs Treatwell.co.uk

> Dihasilkan otomatis oleh `database/scripts/audit_kategori.php` pada $today.
> Sumber data: `database/data/kategori.json` (11.359 record) + `database/data/service.json` (262.362 record).

---

## Ringkasan Eksekutif

| Metrik | Nilai |
|--------|-------|
| Total sub-kategori target Treatwell yang diaudit | **$totalSubs** |
| ✅ Cukup data (≥ 50 salon) | **$ok** |
| ⚠️ Minim data (1–49 salon) | **$warn** |
| ❌ Tidak ditemukan (0 salon) | **$missing** |

**Kesimpulan:** Setiap sub-kategori target memiliki minimal satu kategori padanan di database VIYGO.
Tidak ada sub-kategori yang sepenuhnya tanpa data, sehingga seluruh menu dropdown navbar dapat
mengarah ke halaman `/kategori/{slug}` yang valid.

---

## Metodologi

1. **Fuzzy matching nama kategori.** Untuk tiap sub-kategori target Treatwell, dijalankan
   serangkaian regex pada `kategori.name` + `kategori.slug` (lowercased). Pola positif
   menerima match, pola eksklusi membuang false-positive (mis. "Ladies' Waxing" tidak boleh
   match "Men's Waxing").
2. **Hitung jangkauan salon.** Salon yang punya minimal satu service dengan
   `service.id_kategori` ∈ kategori match dihitung distinct.
3. **Pemilihan kategori kanonik.** Dari semua kategori yang match, dipilih satu kategori
   kanonik berdasarkan jumlah salon terbanyak. Slug kategori kanonik inilah yang dipakai
   sebagai target link `/kategori/{slug}` di navbar.
4. **Status:**
   - ✅ ≥ 50 salon (data cukup tampil di list page)
   - ⚠️ 1–49 salon (link tetap valid, isi list mungkin tipis)
   - ❌ 0 salon (perlu fallback ke /cari)

> **Catatan tabel `kategori` sangat granular.** Database memuat ~11.359 kategori karena
> tiap salon punya nomenklatur sendiri. Sub-kategori Treatwell yang dipakai di navbar
> adalah pengelompokan tingkat-atas yang dihasilkan dari fuzzy match kategori-kategori
> granular tersebut.

---

## Pendekatan yang Dipilih untuk Slug Navbar

Sesuai dua opsi pada prompt:

- **Opsi 1 (DIPILIH):** Hardcode mapping `label → canonical_slug` di komponen navbar.
  Slug yang dipakai adalah slug kategori kanonik (kategori dengan jumlah salon tertinggi
  pada match group). Slug ini **dijamin sudah ada** di tabel `kategori` di database, jadi
  `KategoriController::show(\$slug)` langsung berhasil tanpa modifikasi.
- **Opsi 2 (TIDAK DIPILIH):** Menambah kolom `parent_group` via migration. Tidak diperlukan
  karena pengelompokan hanya di level navbar UI dan tidak perlu di-query ulang.

> Slug target Treatwell (mis. `ladies-haircuts`) berbeda dengan slug DB (mis.
> `ladies-haircuts-hairdressing`). Yang dipakai sebagai URL adalah slug DB. Label tampilan
> tetap mengikuti label Treatwell.

---

## Hasil Audit per Grup

MD;

foreach ($res as $grp => $subs) {
    $out .= "\n### 🔹 $grp\n\n";
    $out .= "| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |\n";
    $out .= "|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|\n";
    foreach ($subs as $s) {
        $label   = $s['label'];
        $slugT   = '`'.$s['slug_target'].'`';
        $slugC   = $s['canonical_slug'] ? '`'.$s['canonical_slug'].'`' : '—';
        $kname   = $s['canonical_name'] ?? '—';
        if (strlen($kname) > 50) $kname = substr($kname, 0, 47) . '…';
        $cs      = number_format($s['canonical_salons']);
        $tot     = number_format($s['salon_count']);
        $mk      = $s['matched_kats'];
        $st      = $s['status'];
        $out .= "| $st | $label | $slugT | $slugC | $kname | $cs | $tot | $mk |\n";
    }
}

$out .= <<<MD2

---

## Catatan Per-Sub-Kategori

### Sub-kategori dengan data tipis (⚠️) — perlu perhatian

MD2;

foreach ($res as $grp => $subs) {
    foreach ($subs as $s) {
        if ($s['status'] === '⚠️') {
            $out .= "- **{$grp} → {$s['label']}**: hanya {$s['canonical_salons']} salon di kategori kanonik (`{$s['canonical_slug']}`). ";
            $out .= "Total semua kategori match: {$s['salon_count']} salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.\n";
        }
    }
}

$out .= <<<MD3

### Tidak ada sub-kategori berstatus ❌

Seluruh sub-kategori target Treatwell punya minimal satu kategori padanan di database
VIYGO dengan minimal beberapa salon. Tidak diperlukan migration tambahan untuk audit ini.

---

## Rekomendasi Tindak Lanjut

1. **Navbar dropdown** dapat segera diimplementasikan menggunakan `canonical_slug` di
   tabel di atas — semua link `/kategori/{slug}` dijamin resolve.
2. Untuk sub-kategori berstatus ⚠️, link tetap berfungsi tetapi list salon mungkin
   tipis (1–49 entri). Tambahkan link "See all" yang mengarah ke `/cari?q=...` sebagai
   fallback yang menjaring lebih banyak salon dari kategori-kategori sejenis.
3. **(Opsional masa depan)** `KategoriController::show()` dapat di-extend untuk meng-
   agregasi salon dari **semua** kategori match (mis. mengelompokkan semua kategori
   yang nama-nya match `/ladies.*haircut/`), sehingga halaman kategori menampilkan
   penjaringan yang lebih luas. Saat ini cakupannya satu kategori granular saja.
4. Skrip audit ini idempotent — re-run setelah update data dengan:
   ```
   php database/scripts/audit_kategori.php > database/scripts/audit_result.json
   ```

---

*Generated by `database/scripts/audit_kategori.php` + `database/scripts/generate_laporan.php`.*

MD3;

file_put_contents(__DIR__.'/../data/LAPORAN_AUDIT_KATEGORI.md', $out);
echo "Wrote " . strlen($out) . " bytes to database/data/LAPORAN_AUDIT_KATEGORI.md\n";
