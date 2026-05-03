# VIYGO — Beauty & Wellness Marketplace

> A Treatwell-style salon discovery and booking platform built on **Laravel 13 + Livewire Flux**, seeded with 8,750+ real UK salons scraped from Treatwell UK.
>
> **Status (May 1, 2026):** Public frontend integrated — homepage, search, category, salon detail (with Leaflet minimap), 3-step booking, account dashboard. UI fully in English, prices in £ GBP.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Public Frontend Routes](#public-frontend-routes)
- [Database Architecture](#database-architecture)
- [Folder Structure](#folder-structure)
- [Installation](#installation)
- [Running the Scraper](#running-the-scraper)
- [Database Seeding](#database-seeding)
- [Branching Strategy](#branching-strategy)
- [Documentation Files](#documentation-files)

---

## Overview

VIYGO is a fully functional Treatwell.co.uk clone that lets users:

- **Search** salons by treatment, location and rating
- **Book** treatments through a 3-step booking flow (Pick Service → Pick Date & Time → Confirm)
- **Find** salons on an interactive Leaflet map (search, category and salon-detail pages)
- **Review** treatments they've enjoyed (model + DB ready)
- **Salon owners** can list their salon (public sign-up form on `/mitra`)
- **Admin panel** is on the roadmap

The catalogue is sourced from a Go-based scraper of Treatwell UK (1,000+ salons in initial drop, 8,750+ after later scrapes; ~190K services).

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend Framework** | Laravel 13 (PHP ^8.3) |
| **Frontend / UI** | Livewire Flux v2 + TailwindCSS v4 (Vite) |
| **Maps** | Leaflet 1.9.4 (OpenStreetMap tiles, loaded via CDN) |
| **Auth** | Laravel Fortify (2FA support) |
| **Database** | MySQL |
| **Scraper** | Go (Golang) — concurrent Treatwell scraper |
| **Package Manager** | Composer (PHP) + npm (JS) |
| **Testing** | PestPHP v4 |

---

## Public Frontend Routes

After integration on May 1, 2026, the following named routes are live:

### Public

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/` | `home` | `HomeController@index` |
| GET | `/cari` | `cari` | `SearchController@index` |
| GET | `/kategori/{slug}` | `kategori.show` | `KategoriController@show` |
| GET | `/salon/{slug}` | `salon.show` | `SalonController@show` |
| GET | `/gift-card` | `gift-card` | `GiftCardController@index` |
| GET | `/lookbook` | `lookbook` | `LookbookController@index` |
| GET | `/treatment-files` | `treatment-files` | `TreatmentFilesController@index` |
| GET | `/mitra` | `mitra` | `MitraController@index` |

### Auth-protected

| Method | URI | Name |
|--------|-----|------|
| GET | `/salon/{slug}/booking` | `booking.create` |
| POST | `/salon/{slug}/booking` | `booking.store` |
| GET | `/booking/{kode}/konfirmasi` | `booking.konfirmasi` |
| POST | `/booking/{kode}/batal` | `booking.batal` |
| GET | `/akun` | `akun.index` |
| GET | `/akun/bookings` | `akun.bookings` |
| GET | `/akun/favorit` | `akun.favorit` |
| GET | `/akun/pengaturan` | `akun.pengaturan` |
| PUT | `/akun/pengaturan` | `akun.pengaturan.update` |
| GET | `/akun/reward` | `akun.reward` |
| GET | `/dashboard` | `dashboard` |

The `routes/settings.php` file (Fortify-driven settings/security/profile pages) is required at the bottom of `routes/web.php`.

---

## Database Architecture

### Entity Relationships

```
kota ──────────────────────────────────────────────────────┐
│
kategori ──────────────────────────────────────────────┐ │
│ │
users ─────────────────────────────────────────────┐ │ │
│ │ │
salon ←──┘ │
│ └─────────┘
┌──────────┤
│ │
service staff
(FK: salon, (FK: salon)
kategori)
│
staff_service
(pivot: staff ↔ service)

order ← order_detail ← service
│
└→ review
└→ pembayaran
└→ user_promo ← promo
```

### Tables

| Table | Description |
|-------|-------------|
| `users` | Customers, salon owners, admins. Roles: `customer / salon_owner / admin` |
| `kota` | Master data of cities (UK cities scraped from Treatwell) |
| `kategori` | Master treatment categories (Hair, Face, Nails, Body, Massage, etc.) |
| `salon` | Salon profile: name, address, GPS coords, opening hours, rating + **`slug`** for friendly URLs |
| `service` | Treatments per salon: name, price (£), duration, category |
| `staff` | Stylists per salon |
| `staff_service` | Pivot — which staff can deliver which service |
| `staff_schedule` | Working hours per staff member |
| `salon_images` | Salon gallery photos (`image_url`, with `url` accessor) |
| `promo` | Promotions and discounts |
| `order` | Booking transactions |
| `order_detail` | Per-service line items on a booking + **`catatan`** (note) field |
| `pembayaran` | Payment records |
| `review` | Customer ratings + comments |
| `user_promo` | Pivot — promo redemption per user |

**Schema additions on May 1, 2026:**
- `salon.slug` (string 200, unique) — backfilled for all 5,767 rows
- `order_detail.catatan` (text, nullable) — free-text customer note on a booking line

---

## Folder Structure

```
VIYGO/
├── app/
│ ├── Http/Controllers/ # Public + Account controllers (added May 2026)
│ ├── Livewire/ # Flux scaffolding
│ ├── Models/ # 13 Eloquent models
│ └── Providers/
│
├── database/
│ ├── data/ # Scraped JSON source data
│ │ ├── salon.json
│ │ ├── service.json
│ │ ├── staff.json
│ │ ├── salon_images.json
│ │ ├── kategori.json
│ │ └── kota.json
│ ├── migrations/ # 22 migrations (incl. 3 added May 2026)
│ ├── scripts/ # PHP utilities (validate_json.php, etc.)
│ └── seeders/ # 8 seeders + SalonSlugBackfillSeeder
│
├── resources/
│ ├── css/ # app.css (Tailwind v4 + Flux)
│ ├── js/ # app.js
│ └── views/
│ ├── layouts/
│ │ ├── public.blade.php # Public layout w/ Leaflet CDN
│ │ ├── app.blade.php # Flux dashboard layout
│ │ └── auth/
│ ├── components/
│ │ ├── viygo-logo.blade.php
│ │ ├── viygo-navbar.blade.php
│ │ ├── viygo-footer.blade.php
│ │ ├── salon-card.blade.php
│ │ └── leaflet-map.blade.php # NEW reusable Leaflet component
│ ├── home.blade.php
│ ├── cari/index.blade.php # search results + Leaflet multi-marker map
│ ├── kategori/show.blade.php # category page + Leaflet multi-marker map
│ ├── salon/show.blade.php # detail page + Leaflet single-marker map
│ ├── booking/
│ │ ├── create.blade.php # 3-step Alpine.js wizard
│ │ └── konfirmasi.blade.php
│ ├── akun/ # account dashboard, bookings, favourites, settings, rewards
│ ├── gift-card/index.blade.php
│ ├── lookbook/index.blade.php
│ ├── treatment-files/index.blade.php
│ ├── mitra/index.blade.php # Salon partner sign-up
│ ├── pages/auth/ # Fortify auth pages
│ └── partials/
│
├── routes/
│ ├── web.php # Public + auth-protected routes (May 2026)
│ └── settings.php
│
├── update/ # ARCHIVED — original Indonesian-language frontend drop
│ # (kept for traceability, no longer authoritative)
│
├── INTEGRATION_GUIDE.md # Original integration guide ( COMPLETED)
├── PROGRESS_REPORT.md # Phase-by-phase status of the May 2026 integration
├── LAPORAN_PROYEK.md # Work report (Indonesian + English)
├── progress.md # Long-form progress tracker
├── README.md # You are here
├── composer.json
├── package.json
└── vite.config.js
```

---

## Installation

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8+
- Go 1.22+ *(only needed if you want to re-run the scraper)*

### Steps

```bash
# 1. Clone
git clone https://github.com/yopalll/VIYGO.git
cd VIYGO

# 2. PHP dependencies
composer install

# 3. .env
cp .env.example .env
php artisan key:generate

# 4. Set DB in .env
# DB_CONNECTION=mysql
# DB_DATABASE=viygo-go
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Run migrations (incl. May 2026 additions)
php artisan migrate

# 6. Seed the database
php artisan db:seed

# 7. Backfill slugs for existing salons (only needed once)
php artisan db:seed --class=SalonSlugBackfillSeeder

# 8. Apply unique index on slug (final migration step)
php artisan migrate

# 9. Frontend assets (REQUIRED — without this step, the app returns a 500 error)
npm install
npm run build          # compiles Vite manifest into public/build/
# For development with hot-reload, run `npm run dev` instead.
```

Then visit `http://localhost:8000/` — the homepage should load with featured salons.

---

## Running the Scraper

The scraper is a separate Go project. From the repo root:

```bash
cd viygo-scraper
build.bat # Windows
./scraper.exe --category hair --pages 50 # outputs JSON to ../database/data/
```

See `SCRAPER.md` for the full guide.

---

## Database Seeding

Seeders are idempotent — safe to re-run.

```bash
php artisan db:seed # everything
php artisan db:seed --class=KotaSeeder # individual seeder
php artisan db:seed --class=SalonSlugBackfillSeeder # slug backfill (5,767 rows)
php database/scripts/validate_json.php # JSON sanity-check
```

**Seeder run order:**
1. `KotaSeeder` — cities
2. `KategoriSeeder` — treatment categories (upsert by slug)
3. `UserSeeder` — demo accounts (admin + salon owner + customer)
4. `SalonSeeder` — salons
5. `ServiceSeeder` — treatments per salon
6. `StaffSeeder` — stylists
7. `SalonImagesSeeder` — gallery photos
8. `SalonSlugBackfillSeeder` — slugs (run once after the slug column exists)

---

## Branching Strategy

| Branch | Description |
|--------|-------------|
| `main` | Production-ready (stable) |
| `go-fresh` | Active development |
| `branch-viter` | Vite UI experiments |
| `seeder-hair` | Hair-data seeder development |

---

## Documentation Files

| File | Purpose |
|------|---------|
| [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md) | Original integration guide ( completed May 1, 2026) |
| [PROGRESS_REPORT.md](PROGRESS_REPORT.md) | Phase-by-phase status of the public-frontend integration |
| [LAPORAN_PROYEK.md](LAPORAN_PROYEK.md) | Final work report (mixed Indonesian/English) |
| [progress.md](progress.md) | Long-form progress tracker |
| [docs/](docs/) | Additional internal docs |

---

## Team

VIYGO is developed as an academic project replicating Treatwell.co.uk.

## License

MIT License — see `LICENSE`.
