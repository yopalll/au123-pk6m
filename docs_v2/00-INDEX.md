# VIYGO V2 — Plan Index

> Platform: Laravel 12 + Livewire Flux + TailwindCSS v4 + **Filament v5.6**  
> PRD Referensi: `docs/PRD_VIYGO_V2.md` (v2.3)  
> Cara pakai: buka file plan, copy isinya sebagai prompt ke AI agent, jalankan berurutan sesuai nomor.

> 🔴 **BACA DULU: [CATATAN-LINGKUNGAN.md](CATATAN-LINGKUNGAN.md)** — fakta kode V1 hasil recon Phase 0
> (Filament v5 bukan v3, `User.role` guarded, penamaan Order/Salon/Pembayaran V1). Plan mengikuti dokumen ini.

---

## Urutan Pengerjaan

### PHASE 1 — Foundation (WAJIB selesai semua sebelum Phase 2)

| File | Step | Isi |
|------|------|-----|
| [phase-1a-database.md](phase-1a-database.md) | 1.1 + 1.2 | ALTER users.role + 28 migration tabel baru |
| [phase-1b-models.md](phase-1b-models.md) | 1.3 | 24+ Eloquent Models + relationships |
| [phase-1c-scraper-seed.md](phase-1c-scraper-seed.md) | 1.4 + 1.5 | Go Scraper fresh.com + Laravel Seeders + **Filament Store Panel** |
| [phase-1d-config-nav.md](phase-1d-config-nav.md) | 1.6 + 1.7 | Install DomPDF, config ongkir, update navigasi |

### PHASE 2 — Core Features

| File | Step | Isi |
|------|------|-----|
| [phase-2a-booking-invoice.md](phase-2a-booking-invoice.md) | 2.1 | Modul 5: Rincian Booking + Invoice PDF |
| [phase-2b-ecommerce.md](phase-2b-ecommerce.md) | 2.2 | Modul 1: E-commerce Skincare (10 sub-steps) |

### PHASE 3 — Enhancement (bisa paralel)

| File | Step | Isi |
|------|------|-----|
| [phase-3a-lookbook.md](phase-3a-lookbook.md) | 3.1 | Modul 2: Lookbook Skincare |
| [phase-3b-empty-return.md](phase-3b-empty-return.md) | 3.2 | Modul 3: Empty Return + Poin + Konten Eksklusif |
| [phase-3c-community.md](phase-3c-community.md) | 3.3 | Modul 4: Digital Library Community |

### PHASE 4 — Polish & Testing

| File | Step | Isi |
|------|------|-----|
| [phase-4-polish-testing.md](phase-4-polish-testing.md) | 4.1–4.6 | Cross-module, UI Polish, Testing, Audit, Dokumentasi |

### Design Reference

| Folder | Isi |
|--------|-----|
| [design/](design/README.md) | 74 screen HTML + screenshot dari Stitch (Serene Floral Noir design system) |
| [design/DESIGN-SYSTEM.md](design/DESIGN-SYSTEM.md) | Color tokens, typography, spacing spec |

---

## Dependency Flow

```
Phase 1A (DB) ──► Phase 1B (Models) ──► Phase 1C (Scraper+Seed)
     │
     └──► Phase 1D (Config+Nav)
                │
                ▼
          Phase 2A (Booking Invoice)
          Phase 2B (E-commerce) ◄── butuh 1B + 1C + 1D
                │
                ▼
    ┌───────────┼───────────┐
    ▼           ▼           ▼
Phase 3A    Phase 3B    Phase 3C
(Lookbook) (EmptyRet) (Community)
    └───────────┴───────────┘
                │
                ▼
          Phase 4 (Polish)
```

---

## Checklist Global

```
PHASE 1
  [ ] 1.1+1.2  ALTER users.role + 28 migration tabel baru
  [ ] 1.3      24+ Eloquent Models
  [ ] 1.4+1.5  Go Scraper + Seeders
  [ ] 1.6+1.7  DomPDF + ongkir config + navigasi

PHASE 2
  [ ] 2.1  Rincian Booking + Invoice PDF
  [ ] 2.2  E-commerce Skincare

PHASE 3
  [ ] 3.1  Lookbook
  [ ] 3.2  Empty Return + Poin
  [ ] 3.3  Community Forum

PHASE 4
  [ ] 4.1  Cross-module integration
  [ ] 4.2  Admin Store Dashboard
  [ ] 4.3  UI/UX polish + responsive
  [ ] 4.4  Testing
  [ ] 4.5  Performance & security audit
  [ ] 4.6  Dokumentasi final
```
