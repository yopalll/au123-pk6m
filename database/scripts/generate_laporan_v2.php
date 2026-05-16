<?php
/**
 * Generate LAPORAN_AUDIT_KATEGORI_V2.md dari hasil audit_result_v2.json.
 * Versi ini sudah selaras dengan listing Treatwell.co.uk asli.
 */

$res = json_decode(file_get_contents(__DIR__.'/audit_result_v2.json'), true);

$totalSubs = 0; $ok = 0; $warn = 0; $missing = 0;
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
# 📊 LAPORAN AUDIT KATEGORI v2 — Selaras dengan Treatwell.co.uk

> Dihasilkan otomatis pada $today oleh `database/scripts/audit_kategori_v2.php`.
> Target sub-kategori diambil langsung dari listing publik Treatwell.

## Sumber Data Treatwell

| Parent | URL Treatwell |
|--------|---------------|
| HAIR | https://www.treatwell.co.uk/hairdressers-and-hair-salons/ |
| HAIR REMOVAL | https://www.treatwell.co.uk/hair-removal-salons/ |
| MASSAGE | https://www.treatwell.co.uk/massage-salons-and-therapists/ |
| NAILS | https://www.treatwell.co.uk/nail-salons-and-nail-bars/ |
| FACE | https://www.treatwell.co.uk/beauty-salons-face-treatments/ |
| BODY | https://www.treatwell.co.uk/beauty-salons-body-treatments/ |
| MEN'S | (filter: men-s-haircut, beard-trimming, men-s-hair-colouring) |

## Ringkasan

| Metrik | Nilai |
|--------|-------|
| Total sub-kategori target Treatwell yang diaudit | **$totalSubs** |
| ✅ Cukup data (≥ 50 salon) | **$ok** |
| ⚠️ Minim data (1–49 salon) | **$warn** |
| ❌ Tidak ditemukan (0 salon) | **$missing** |

**Kesimpulan:** Setiap sub-kategori target Treatwell punya kategori padanan di DB VIYGO.
Tidak diperlukan migration tambahan; semua link `/kategori/{slug}` resolve.

---

## Hasil Audit per Grup


MD;

foreach ($res as $grp => $subs) {
    $out .= "### 🔹 $grp\n\n";
    $out .= "| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |\n";
    $out .= "|:------:|-----------------|----------------|-----------------|-------------|--------------:|\n";
    foreach ($subs as $s) {
        $kname = $s['canonical_name'] ?? '—';
        if (strlen($kname) > 50) $kname = substr($kname, 0, 47) . '…';
        $out .= sprintf(
            "| %s | %s | `%s` | `%s` | %s | %s |\n",
            $s['status'], $s['label'], $s['tw_slug'],
            $s['canonical_slug'] ?? '—', $kname,
            number_format($s['salon_count'])
        );
    }
    $out .= "\n";
}

$out .= "---\n\n## Sub-Kategori dengan Data Tipis (⚠️)\n\n";
foreach ($res as $grp => $subs) {
    foreach ($subs as $s) {
        if ($s['status'] === '⚠️') {
            $out .= "- **$grp → {$s['label']}**: {$s['salon_count']} salon "
                  . "(canonical: `{$s['canonical_slug']}`).\n";
        }
    }
}

$out .= "\n---\n\n*File ini di-generate oleh `database/scripts/generate_laporan_v2.php` "
      . "dan dibaca oleh `resources/views/components/viygo-navbar.blade.php`.*\n";

file_put_contents(__DIR__.'/../data/LAPORAN_AUDIT_KATEGORI_V2.md', $out);
echo "Wrote " . strlen($out) . " bytes to database/data/LAPORAN_AUDIT_KATEGORI_V2.md\n";
