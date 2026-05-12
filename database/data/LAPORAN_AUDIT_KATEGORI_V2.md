# 📊 LAPORAN AUDIT KATEGORI v2 — Selaras dengan Treatwell.co.uk

> Dihasilkan otomatis pada 2026-05-07 oleh `database/scripts/audit_kategori_v2.php`.
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
| Total sub-kategori target Treatwell yang diaudit | **48** |
| ✅ Cukup data (≥ 50 salon) | **30** |
| ⚠️ Minim data (1–49 salon) | **18** |
| ❌ Tidak ditemukan (0 salon) | **0** |

**Kesimpulan:** Setiap sub-kategori target Treatwell punya kategori padanan di DB VIYGO.
Tidak diperlukan migration tambahan; semua link `/kategori/{slug}` resolve.

---

## Hasil Audit per Grup

### 🔹 HAIR

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Ladies' Haircuts | `ladies-haircuts-1` | `ladies-haircuts-hairdressing` | Ladies' - Haircuts & Hairdressing | 1,732 |
| ✅ | Blow Dry | `blow-dry` | `blow-dry` | Blow Dry | 363 |
| ✅ | Hair Colouring | `hair-colouring` | `ladies-highlights-balayage` | Ladies - Highlights & Balayage | 1,809 |
| ✅ | Ladies' Brazilian Blow Dry | `ladies-brazilian-blow-dry` | `brazilian-blow-dry` | Brazilian Blow Dry | 76 |
| ✅ | Balayage | `balayage` | `ladies-highlights-balayage` | Ladies - Highlights & Balayage | 930 |
| ✅ | Hair Extensions | `hair-extensions` | `hair-extensions` | Hair Extensions | 498 |
| ✅ | Men's Haircut | `men-s-haircut` | `men-haircuts-grooming` | Men - Haircuts & Grooming | 1,030 |

### 🔹 HAIR REMOVAL

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Ladies' Waxing | `ladies-waxing` | `ladies-waxing` | Ladies' Waxing | 2,301 |
| ⚠️ | Hollywood Waxing | `hollywood-waxing` | `ladies-waxing-hollywood-hot-wax` | Ladies' Waxing-Hollywood(Hot Wax) | 18 |
| ⚠️ | Brazilian Waxing | `brazilian-waxing` | `ladies-waxing-brazilian-hot-wax` | Ladies' Waxing-Brazilian(Hot Wax) | 9 |
| ✅ | Facial Threading | `facial-threading` | `facial-threading` | Facial Threading | 1,201 |
| ✅ | Men's Waxing | `men-s-waxing` | `men-s-waxing` | Men's Waxing | 1,104 |
| ⚠️ | Ladies' Leg Waxing | `ladies-leg-waxing` | `ladies-waxing-leg` | Ladies' Waxing-Leg | 8 |
| ✅ | Sugaring | `sugaring` | `ladies-sugaring` | Ladies' Sugaring | 68 |

### 🔹 MASSAGE

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ⚠️ | Deep Tissue Massage | `deep-tissue-massage` | `deep-tissue-massage` | Deep Tissue Massage | 40 |
| ⚠️ | Swedish Massage | `swedish-massage` | `swedish-massage` | Swedish Massage | 24 |
| ✅ | Therapeutic Massage | `therapeutic-massage` | `therapeutic-massages` | Therapeutic Massages | 819 |
| ⚠️ | Thai Massage | `thai-massage` | `thai-massages` | Thai Massages | 29 |
| ⚠️ | Aromatherapy Massage | `aromatherapy-massage` | `aromatherapy` | Aromatherapy | 16 |
| ⚠️ | Sports Massage | `sports-massage` | `sports-massage` | Sports Massage | 43 |
| ⚠️ | Hot Stone Massage | `hot-stone-massage` | `hot-stone-massages` | Hot Stone Massages | 10 |

### 🔹 NAILS

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Pedicure | `pedicure` | `manicures-pedicures` | Manicures & Pedicures | 2,183 |
| ✅ | Manicure | `manicure` | `manicures-pedicures` | Manicures & Pedicures | 2,066 |
| ✅ | Gel Nails Manicure | `gel-nails-manicure` | `gel-manicures-pedicures` | Gel Manicures & Pedicures | 293 |
| ✅ | Hard Gel Extensions & Overlays | `hard-gel-extensions-overlays` | `nail-extensions-enhancements` | Nail Extensions & Enhancements | 1,191 |
| ✅ | Gel Nails Pedicure | `gel-nails-pedicure` | `gel-manicures-pedicures` | Gel Manicures & Pedicures | 254 |
| ⚠️ | Nail or Gel Polish Removal | `nail-or-gel-polish-removal` | `gel-removal` | Gel Removal | 35 |
| ✅ | Nail Art | `nail-art` | `nail-art-extras` | Nail Art & Extras | 173 |

### 🔹 FACE

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Classic Facials | `classic-facials` | `classic-facials` | Classic Facials | 211 |
| ✅ | Eyelash Extensions | `eyelash-extensions` | `eyelash-extensions` | Eyelash Extensions | 1,383 |
| ✅ | Eyebrow & Eyelash Tinting | `eyebrow` | `eyebrow-eyelash-tinting` | Eyebrow & Eyelash Tinting | 252 |
| ⚠️ | Eyebrow Threading | `eyebrow-threading` | `eyebrow-threading` | Eyebrow Threading | 23 |
| ⚠️ | Eyebrow Waxing | `eyebrow-waxing` | `eyebrow-waxing` | Eyebrow Waxing | 35 |
| ✅ | Brow Definition | `brow-definition` | `eyebrow-design-definition` | Eyebrow Design & Definition | 283 |
| ✅ | Lash Lift | `lash-lift` | `eyelash-extensions-lifts` | Eyelash Extensions & Lifts | 295 |

### 🔹 BODY

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Spray Tanning and Sunless Tanning | `spray-tanning-and-sunless-tanning` | `tanning` | Tanning | 321 |
| ⚠️ | Colonic Hydrotherapy | `colonic-hydrotherapy` | `colon-hydrotherapy` | Colon Hydrotherapy | 7 |
| ⚠️ | Body Wraps | `body-wraps` | `body-wrap` | Body Wrap | 6 |
| ⚠️ | Cryolipolysis | `cryolipolysis` | `fat-freezing` | Fat Freezing | 22 |
| ✅ | Body Exfoliation Treatments | `body-exfoliation-treatments` | `body-exfoliation` | Body Exfoliation | 85 |
| ✅ | Cellulite Treatments | `cellulite-treatments` | `weight-loss-cellulite-treatments` | Weight Loss & Cellulite Treatments | 639 |
| ✅ | Weight Loss Treatments | `weight-loss-treatments` | `weight-loss-cellulite-treatments` | Weight Loss & Cellulite Treatments | 645 |

### 🔹 MEN'S

| Status | Label Treatwell | Slug Treatwell | Slug DB Kanonik | Kategori DB | # Salon Total |
|:------:|-----------------|----------------|-----------------|-------------|--------------:|
| ✅ | Men's Haircut | `men-s-haircut` | `men-haircuts-grooming` | Men - Haircuts & Grooming | 1,030 |
| ⚠️ | Beard Trimming & Shaving | `beard-trimming` | `beard-care` | Beard Care | 46 |
| ⚠️ | Men's Hair Colouring | `men-s-hair-colouring` | `men-s-haircuts-hairdressing-colouring-highlights` | Men's Haircuts, Hairdressing, Colouring & Highl… | 25 |
| ✅ | Men's Facials | `men-s-facials` | `facial-treatments` | Facial Treatments | 272 |
| ✅ | Men's Waxing | `men-s-waxing` | `men-s-waxing` | Men's Waxing | 1,104 |
| ⚠️ | Barbers | `barbers` | `bespoke-barber-services` | Bespoke Barber Services | 23 |

---

## Sub-Kategori dengan Data Tipis (⚠️)

- **HAIR REMOVAL → Hollywood Waxing**: 18 salon (canonical: `ladies-waxing-hollywood-hot-wax`).
- **HAIR REMOVAL → Brazilian Waxing**: 9 salon (canonical: `ladies-waxing-brazilian-hot-wax`).
- **HAIR REMOVAL → Ladies' Leg Waxing**: 8 salon (canonical: `ladies-waxing-leg`).
- **MASSAGE → Deep Tissue Massage**: 40 salon (canonical: `deep-tissue-massage`).
- **MASSAGE → Swedish Massage**: 24 salon (canonical: `swedish-massage`).
- **MASSAGE → Thai Massage**: 29 salon (canonical: `thai-massages`).
- **MASSAGE → Aromatherapy Massage**: 16 salon (canonical: `aromatherapy`).
- **MASSAGE → Sports Massage**: 43 salon (canonical: `sports-massage`).
- **MASSAGE → Hot Stone Massage**: 10 salon (canonical: `hot-stone-massages`).
- **NAILS → Nail or Gel Polish Removal**: 35 salon (canonical: `gel-removal`).
- **FACE → Eyebrow Threading**: 23 salon (canonical: `eyebrow-threading`).
- **FACE → Eyebrow Waxing**: 35 salon (canonical: `eyebrow-waxing`).
- **BODY → Colonic Hydrotherapy**: 7 salon (canonical: `colon-hydrotherapy`).
- **BODY → Body Wraps**: 6 salon (canonical: `body-wrap`).
- **BODY → Cryolipolysis**: 22 salon (canonical: `fat-freezing`).
- **MEN'S → Beard Trimming & Shaving**: 46 salon (canonical: `beard-care`).
- **MEN'S → Men's Hair Colouring**: 25 salon (canonical: `men-s-haircuts-hairdressing-colouring-highlights`).
- **MEN'S → Barbers**: 23 salon (canonical: `bespoke-barber-services`).

---

*File ini di-generate oleh `database/scripts/generate_laporan_v2.php` dan dibaca oleh `resources/views/components/viygo-navbar.blade.php`.*
