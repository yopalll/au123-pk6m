# 📊 LAPORAN AUDIT KATEGORI — VIYGO vs Treatwell.co.uk

> Dihasilkan otomatis oleh `database/scripts/audit_kategori.php` pada 2026-05-07.
> Sumber data: `database/data/kategori.json` (11.359 record) + `database/data/service.json` (262.362 record).

---

## Ringkasan Eksekutif

| Metrik | Nilai |
|--------|-------|
| Total sub-kategori target Treatwell yang diaudit | **43** |
| ✅ Cukup data (≥ 50 salon) | **26** |
| ⚠️ Minim data (1–49 salon) | **17** |
| ❌ Tidak ditemukan (0 salon) | **0** |

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
  `KategoriController::show($slug)` langsung berhasil tanpa modifikasi.
- **Opsi 2 (TIDAK DIPILIH):** Menambah kolom `parent_group` via migration. Tidak diperlukan
  karena pengelompokan hanya di level navbar UI dan tidak perlu di-query ulang.

> Slug target Treatwell (mis. `ladies-haircuts`) berbeda dengan slug DB (mis.
> `ladies-haircuts-hairdressing`). Yang dipakai sebagai URL adalah slug DB. Label tampilan
> tetap mengikuti label Treatwell.

---

## Hasil Audit per Grup

### 🔹 HAIR

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Ladies' Haircuts | `ladies-haircuts` | `ladies-haircuts-hairdressing` | Ladies' - Haircuts & Hairdressing | 848 | 1,745 | 142 |
| ✅ | Blow Dry | `blow-dry` | `blow-dry` | Blow Dry | 104 | 476 | 250 |
| ✅ | Ladies' Hair Colouring & Highlights | `ladies-hair-colouring-highlights` | `ladies-highlights-balayage` | Ladies - Highlights & Balayage | 725 | 1,437 | 340 |
| ✅ | Ladies' Brazilian Blow Dry | `ladies-brazilian-blow-dry` | `brazilian-blow-dry` | Brazilian Blow Dry | 8 | 82 | 68 |
| ✅ | Balayage & Ombre | `balayage-ombre` | `ladies-highlights-balayage` | Ladies - Highlights & Balayage | 725 | 948 | 149 |
| ✅ | Men's Haircut | `mens-haircut` | `men-haircuts-grooming` | Men - Haircuts & Grooming | 654 | 1,031 | 85 |

### 🔹 HAIR REMOVAL

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Facial Threading | `facial-threading` | `facial-threading` | Facial Threading | 855 | 1,261 | 176 |
| ✅ | Ladies' Waxing | `ladies-waxing` | `ladies-waxing` | Ladies' Waxing | 1,844 | 2,301 | 375 |
| ✅ | Sugaring | `sugaring` | `ladies-sugaring` | Ladies' Sugaring | 49 | 68 | 32 |
| ⚠️ | Hollywood Waxing | `hollywood-waxing` | `ladies-waxing-hollywood-hot-wax` | Ladies' Waxing-Hollywood(Hot Wax) | 1 | 19 | 20 |
| ✅ | Men's Waxing | `mens-waxing` | `men-s-waxing` | Men's Waxing | 923 | 1,142 | 152 |
| ⚠️ | Ladies' Leg Waxing | `ladies-leg-waxing` | `ladies-waxing-leg` | Ladies' Waxing-Leg | 1 | 8 | 8 |

### 🔹 MASSAGE

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ⚠️ | Deep Tissue Massage | `deep-tissue-massage` | `deep-tissue-massage` | Deep Tissue Massage | 12 | 40 | 26 |
| ⚠️ | Swedish Massage | `swedish-massage` | `swedish-massage` | Swedish Massage | 16 | 24 | 10 |
| ✅ | Therapeutic Massage | `therapeutic-massage` | `therapeutic-massages` | Therapeutic Massages | 728 | 835 | 78 |
| ⚠️ | Thai Massage | `thai-massage` | `thai-massages` | Thai Massages | 4 | 29 | 29 |
| ⚠️ | Aromatherapy Massage | `aromatherapy-massage` | `aromatherapy` | Aromatherapy | 3 | 16 | 14 |
| ⚠️ | Hot Stone Massage | `hot-stone-massage` | `hot-stone-massages` | Hot Stone Massages | 2 | 10 | 9 |

### 🔹 NAILS

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Pedicure | `pedicure` | `manicures-pedicures` | Manicures & Pedicures | 1,582 | 2,183 | 268 |
| ✅ | Manicure | `manicure` | `manicures-pedicures` | Manicures & Pedicures | 1,582 | 2,066 | 276 |
| ⚠️ | Nail or Gel Polish Removal | `nail-or-gel-polish-removal` | `gel-removal` | Gel Removal | 5 | 35 | 34 |
| ✅ | Gel Nails Manicure | `gel-nails-manicure` | `gel-manicures-pedicures` | Gel Manicures & Pedicures | 111 | 295 | 127 |
| ✅ | Gel Nails Pedicure | `gel-nails-pedicure` | `gel-manicures-pedicures` | Gel Manicures & Pedicures | 111 | 254 | 98 |
| ✅ | Acrylic, Hard Gel & Nail Extensions | `acrylic-hard-gel-nail-extensions` | `nail-extensions-enhancements` | Nail Extensions & Enhancements | 776 | 1,191 | 266 |

### 🔹 FACE

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Classic Facials | `classic-facials` | `classic-facials` | Classic Facials | 103 | 211 | 61 |
| ✅ | Eyelash Extensions | `eyelash-extensions` | `eyelash-extensions` | Eyelash Extensions | 1,134 | 1,386 | 159 |
| ✅ | Eyebrow and Eyelash Tinting | `eyebrow-and-eyelash-tinting` | `eyebrow-eyelash-tinting` | Eyebrow & Eyelash Tinting | 144 | 252 | 86 |
| ⚠️ | Eyebrow Threading | `eyebrow-threading` | `eyebrow-threading` | Eyebrow Threading | 2 | 23 | 20 |
| ⚠️ | Eyebrow Waxing | `eyebrow-waxing` | `eyebrow-waxing` | Eyebrow Waxing | 2 | 35 | 33 |
| ✅ | Definition Brows | `definition-brows` | `eyebrow-design-definition` | Eyebrow Design & Definition | 176 | 286 | 84 |

### 🔹 BODY

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Spray Tanning and Sunless Tanning | `spray-tanning-and-sunless-tanning` | `tanning` | Tanning | 240 | 321 | 41 |
| ✅ | Body Exfoliation Treatments | `body-exfoliation-treatments` | `body-exfoliation` | Body Exfoliation | 53 | 85 | 27 |
| ⚠️ | Body Wraps | `body-wraps` | `body-wrap` | Body Wrap | 1 | 6 | 6 |
| ⚠️ | Colonic Hydrotherapy | `colonic-hydrotherapy` | `colon-hydrotherapy` | Colon Hydrotherapy | 3 | 7 | 5 |
| ⚠️ | Cryolipolysis | `cryolipolysis` | `fat-freezing` | Fat Freezing | 3 | 22 | 19 |
| ✅ | Cellulite Treatments | `cellulite-treatments` | `weight-loss-cellulite-treatments` | Weight Loss & Cellulite Treatments | 557 | 639 | 86 |

### 🔹 MEN'S

| Status | Sub-Kategori (Treatwell) | Slug Target | Slug DB Kanonik | Kategori DB | # Salon (kanonik) | # Salon (semua match) | Match Kats |
|:------:|--------------------------|-------------|-----------------|-------------|------------------:|----------------------:|-----------:|
| ✅ | Men's Haircut | `mens-haircut` | `men-haircuts-grooming` | Men - Haircuts & Grooming | 654 | 1,031 | 85 |
| ⚠️ | Beard Trims and Shaves | `beard-trims-and-shaves` | `beard-care` | Beard Care | 27 | 46 | 21 |
| ⚠️ | Men's Hair Colouring | `mens-hair-colouring` | `men-s-haircuts-hairdressing-colouring-highlights` | Men's Haircuts, Hairdressing, Colouring & Highl… | 3 | 25 | 23 |
| ⚠️ | Men's Brazilian Blow Dry | `mens-brazilian-blow-dry` | `keratin-treatments` | Keratin Treatments | 4 | 21 | 19 |
| ✅ | Men's Facials | `mens-facials` | `facial-treatments` | Facial Treatments | 41 | 272 | 196 |
| ✅ | Men's Waxing | `mens-waxing` | `men-s-waxing` | Men's Waxing | 923 | 1,142 | 152 |
| ⚠️ | Barbers | `barbers` | `bespoke-barber-services` | Bespoke Barber Services | 5 | 23 | 17 |

---

## Catatan Per-Sub-Kategori

### Sub-kategori dengan data tipis (⚠️) — perlu perhatian
- **HAIR REMOVAL → Hollywood Waxing**: hanya 1 salon di kategori kanonik (`ladies-waxing-hollywood-hot-wax`). Total semua kategori match: 19 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **HAIR REMOVAL → Ladies' Leg Waxing**: hanya 1 salon di kategori kanonik (`ladies-waxing-leg`). Total semua kategori match: 8 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MASSAGE → Deep Tissue Massage**: hanya 12 salon di kategori kanonik (`deep-tissue-massage`). Total semua kategori match: 40 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MASSAGE → Swedish Massage**: hanya 16 salon di kategori kanonik (`swedish-massage`). Total semua kategori match: 24 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MASSAGE → Thai Massage**: hanya 4 salon di kategori kanonik (`thai-massages`). Total semua kategori match: 29 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MASSAGE → Aromatherapy Massage**: hanya 3 salon di kategori kanonik (`aromatherapy`). Total semua kategori match: 16 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MASSAGE → Hot Stone Massage**: hanya 2 salon di kategori kanonik (`hot-stone-massages`). Total semua kategori match: 10 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **NAILS → Nail or Gel Polish Removal**: hanya 5 salon di kategori kanonik (`gel-removal`). Total semua kategori match: 35 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **FACE → Eyebrow Threading**: hanya 2 salon di kategori kanonik (`eyebrow-threading`). Total semua kategori match: 23 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **FACE → Eyebrow Waxing**: hanya 2 salon di kategori kanonik (`eyebrow-waxing`). Total semua kategori match: 35 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **BODY → Body Wraps**: hanya 1 salon di kategori kanonik (`body-wrap`). Total semua kategori match: 6 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **BODY → Colonic Hydrotherapy**: hanya 3 salon di kategori kanonik (`colon-hydrotherapy`). Total semua kategori match: 7 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **BODY → Cryolipolysis**: hanya 3 salon di kategori kanonik (`fat-freezing`). Total semua kategori match: 22 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MEN'S → Beard Trims and Shaves**: hanya 27 salon di kategori kanonik (`beard-care`). Total semua kategori match: 46 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MEN'S → Men's Hair Colouring**: hanya 3 salon di kategori kanonik (`men-s-haircuts-hairdressing-colouring-highlights`). Total semua kategori match: 25 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MEN'S → Men's Brazilian Blow Dry**: hanya 4 salon di kategori kanonik (`keratin-treatments`). Total semua kategori match: 21 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.
- **MEN'S → Barbers**: hanya 5 salon di kategori kanonik (`bespoke-barber-services`). Total semua kategori match: 23 salon. Pertimbangkan agregasi multi-kategori atau fallback ke `/cari?q=...`.

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
