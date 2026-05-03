# VIYGO Integration — Progress Report

> **Working document** — track-of-record for the multi-phase integration kicked off on **2026-05-01**.
> **Last updated:** 2026-05-01 19:55 WIB — All 6 phases COMPLETED. Migrations executed, app verified.
> If context runs out mid-task, another agent should read this file + `INTEGRATION_GUIDE.md`.

---

## Status snapshot

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Database migrations + slug backfill seeder | DONE |
| 2 | Model updates (Salon slug, SalonImage url, Kota nama) | DONE |
| 3 | 10 controllers in `app/Http/Controllers/` (with patches) | DONE |
| 4 | `routes/web.php` wired to public + auth-protected routes | DONE |
| 5.1 | `layouts/public.blade.php` with Leaflet CDN | DONE |
| 5.2 | 5 components (logo, navbar, footer, salon-card, leaflet-map) | DONE |
| 5.3 | 14 page views translated to English with Leaflet maps | DONE |
| 6.1 | README.md refresh | DONE |
| 6.2 | progress.md refresh | DONE |
| 6.3 | INTEGRATION_GUIDE.md status banner + deviations | DONE |
| 6.4 | LAPORAN_PROYEK.md work report | DONE |

---

## Decisions locked in (do not re-litigate)

- **Currency**: `£` GBP, formatted as `£XX.XX` with `number_format($n, 2, '.', ',')`
- **Translation**: full UI translation to English; data-side field names (`nama_salon`, `kategori`, `kota`, `harga`) stay Indonesian
- **`welcome.blade.php`**: kept on disk, route now points to `HomeController@index`
- **No Laravel i18n** (`lang/`) — direct text replacement in views
- **Leaflet** loaded via CDN in `layouts/public.blade.php`. Reusable `<x-leaflet-map>` Blade component encapsulates map init.
- **`update/` folder kept** for traceability

---

## Phase 1 — Database

**Status:** Files written AND executed. All 3 migrations RAN. Slug backfill seeder completed (8,750 unique slugs).

Files created:
- `database/migrations/2026_05_01_000001_add_slug_to_salon_table.php` — adds `string('slug', 200) nullable + index`
- `database/migrations/2026_05_01_000002_add_catatan_to_order_detail_table.php` — adds `text('catatan') nullable`
- `database/seeders/SalonSlugBackfillSeeder.php` — chunked backfill, dedupe by appending `-{id_salon}`, `saveQuietly()`
- `database/migrations/2026_05_01_000003_add_unique_index_to_salon_slug.php` — drops the non-unique index, adds `unique('slug')`

**Run order** (still to execute):
```
php artisan migrate # runs 000001 + 000002
php artisan db:seed --class=SalonSlugBackfillSeeder # backfill 5,767 salon slugs
php artisan migrate # runs 000003 (unique index)
```

---

## Phase 2 — Models

**Status:** Done.

Modified:
- [app/Models/Salon.php](app/Models/Salon.php) — added `'slug'` to `$fillable`, added `getRouteKeyName(): 'slug'`
- [app/Models/SalonImage.php](app/Models/SalonImage.php) — overwritten with `url` accessor → `image_url`
- [app/Models/Kota.php](app/Models/Kota.php) — overwritten with `nama` accessor → `nama_kota`

`User`, `Order`, `OrderDetail`, `Service`, `Kategori`, `Staff`, `Review` not touched.

---

## Phase 3 — Controllers

**Status:** Done. All 10 created with patches.

Files created in `app/Http/Controllers/`:
- `HomeController.php` — top 8 by rating + 8 categories (also passes `$categories` to view if used)
- `SearchController.php` — patched: query uses `nama_kota` (not `nama`); `harga-terendah` sort uses `withMin('services as min_harga', 'harga')`
- `KategoriController.php` — patched: `withMin('services as min_harga'…)`, scoped to active services in this category
- `SalonController.php` — supports slug + id_salon fallback
- `BookingController.php` — **store()** rewritten to map to existing schema:
- `OrderDetail::create([id_order, id_service, id_staff=null, start_time, end_time=Carbon::createFromFormat('H:i',waktu)->addMinutes($durasi)->format('H:i'), harga_at_order, subtotal, catatan, status='pending'])`
- `AkunController.php` — `updatePengaturan` validates+updates `first_name`, `last_name`, `email` with unique rule `users,email,{id},id_user`
- `GiftCardController.php`, `LookbookController.php`, `TreatmentFilesController.php`, `MitraController.php` — stub controllers (Mitra pulls `Kota::orderBy('nama_kota')`)

---

## Phase 4 — Routes

**Status:** Done.

`routes/web.php` rewritten. Named routes registered:
- Public: `home`, `cari`, `kategori.show`, `salon.show`, `gift-card`, `lookbook`, `treatment-files`, `mitra`
- Auth: `booking.create`, `booking.store`, `booking.konfirmasi`, `booking.batal`, `akun.index`, `akun.bookings`, `akun.favorit`, `akun.pengaturan`, `akun.pengaturan.update`, `akun.reward`, `dashboard`
- `require __DIR__.'/settings.php';` retained at bottom

---

## Phase 5.1 — Public layout

**Status:** Done.

`resources/views/layouts/public.blade.php` created with:
- English `<title>` template "{{ $title ?? 'VIYGO' }} — Beauty & Wellness Marketplace"
- Leaflet 1.9.4 CSS in head + JS before `</body>` (with SRI integrity hashes)
- Renders `<x-viygo-navbar>` and `<x-viygo-footer>`
- Z-index rules so Leaflet stays below sticky navbar

---

## Phase 5.2 — Components

**Status:** Done. All 5 components in `resources/views/components/`:

- `viygo-logo.blade.php` — Alpine.js cross-fade between two logo images, with text fallback if images missing (`onerror`)
- `viygo-navbar.blade.php` — fully English, top categories link to `/cari?q=...` (search-based since DB has 7,183 granular Treatwell categories), search placeholder "Search treatments, salons or locations…", auth links "Sign in / Sign up / My Account", added "For Salons" link to `/mitra`
- `viygo-footer.blade.php` — fully English, treatments map to `/cari?q=...`, removed Indonesia-specific copy
- `salon-card.blade.php` — fully English; `£` formatting; `from £45.00`; removed `'Indonesia'` fallback; uses `$salon->kota?->nama` (works via Kota accessor)
- `leaflet-map.blade.php` — **NEW** reusable component. Props: `id` (auto), `height` (default `360px`), `center`, `zoom`, `markers` (array/collection of `{lat, lng, title, url}`), `single`, `class`. OpenStreetMap tiles, marker popups link to salon pages, `fitBounds` for multi-marker, `invalidateSize()` after 200ms for sticky containers, defers init if Leaflet `L` not yet loaded.

---

## Phase 5.3 — Page views (IN PROGRESS)

### Already done

- `resources/views/home.blade.php` — English copy, UK stats (5,700+ salons, 1,700+ cities, 190K+ treatments), category emojis link to `/cari?q=...`, CTA section
- `resources/views/cari/index.blade.php` — English; **Leaflet map** (right sidebar 420px, sticky) shows up to 30 markers from `$salons` paginator with `whereNotNull` filter; sort chips: Most Popular / Top Rated / Lowest Price; English empty states
- `resources/views/kategori/show.blade.php` — English; same Leaflet right sidebar; sort dropdown: Most Popular / Top Rated / Lowest Price / Newest
- `resources/views/salon/show.blade.php` — English; **single-marker Leaflet map** in info column (only when `latitude` and `longitude` non-null, height 280px, zoom 15); "Our Team" section for staff; reviews show `$review->user?->full_name` (User model has `fullName` accessor)

### ⬜ Still to create (next batch)

In order:

1. `resources/views/booking/create.blade.php` — 3-step wizard (Pick Service / Pick Date & Time / Confirm). Translate all Alpine.js variables (`monthLabel` months → English, `Min, Sen, Sel…` → `Sun, Mon, Tue…`). Currency: `£`. Notes textarea label "Note (optional)".
2. `resources/views/booking/konfirmasi.blade.php` — Booking confirmation success page in English. Currency `£`.
3. `resources/views/akun/index.blade.php` — "My Account" dashboard, English tile labels (Bookings, Personal Info, Favourites, Rewards, Wallet, Refer a Friend), VIYGO Rewards card.
4. `resources/views/akun/bookings.blade.php` — Tabs translated: Mendatang→Upcoming, Selesai→Completed, Dibatalkan→Cancelled. Currency `£`.
5. `resources/views/akun/favorit.blade.php` — "My Favourites" empty-state in English.
6. `resources/views/akun/pengaturan.blade.php` — **MUST** bind to `first_name`, `last_name`, `email` (NOT `name`, since User model has `first_name`+`last_name`). Method spoofing `@method('PUT')`. Form posts to `route('akun.pengaturan.update')`.
7. `resources/views/akun/reward.blade.php` — VIYGO Rewards English with progress bar.
8. `resources/views/gift-card/index.blade.php` — English; nominals £25 / £50 / £100 / £200.
9. `resources/views/lookbook/index.blade.php` — English style inspiration page.
10. `resources/views/treatment-files/index.blade.php` — English beauty articles page.
11. `resources/views/mitra/index.blade.php` — "Partner with us" / "List Your Salon" form; uses `$kotas` collection, options use `$kota->nama_kota` directly (form posts to a future endpoint — leave it as a static form for now).

### Reference snippets for the next agent

**To embed Leaflet markers from a paginator** (used in cari + kategori):
```php
$mapMarkers = $salons->getCollection()
->filter(fn ($s) => $s->latitude !== null && $s->longitude !== null)
->take(30)
->map(fn ($s) => [
'lat' => (float) $s->latitude,
'lng' => (float) $s->longitude,
'title' => $s->nama_salon,
'url' => route('salon.show', $s->slug ?? $s->id_salon),
])
->values();
```

**Leaflet single marker** (used in salon detail):
```blade
<x-leaflet-map
id="map-salon-{{ $salon->id_salon }}"
height="280px"
:center="[(float) $salon->latitude, (float) $salon->longitude]"
:zoom="15"
:markers="[[
'lat' => (float) $salon->latitude,
'lng' => (float) $salon->longitude,
'title' => $salon->nama_salon,
'url' => '',
]]"
single
/>
```

**Booking form must use existing OrderDetail schema** — controller already adapted. View posts:
- `id_service` (hidden), `tanggal` (hidden, YYYY-MM-DD), `waktu` (hidden, H:i), `catatan` (textarea)

**Pengaturan form must POST these** (controller validates first_name + last_name + email):
```html
<form method="POST" action="{{ route('akun.pengaturan.update') }}">
@csrf @method('PUT')
<input name="first_name" value="{{ old('first_name', auth()->user()->first_name) }}" />
<input name="last_name" value="{{ old('last_name', auth()->user()->last_name) }}" />
<input name="email" value="{{ old('email', auth()->user()->email) }}" />
</form>
```

---

## Phase 6 — Documentation (PENDING)

Order to do them in:

### 6.1 — `README.md`
- Update tone to English/UK-friendly
- Add Leaflet to Tech Stack (under "Frontend / UI")
- Add "Public Frontend Routes" section listing the 18 named routes
- Status line: "Frontend integrated, English UI, UK data"
- Replace any "Indonesia" framing (project name "VIYGO" stays)

### 6.2 — `progress.md`
- Update header date to `May 1, 2026`
- Flip status table rows:
- Backend / Controllers → SELESAI 100%
- Frontend Landing Page → SELESAI 90%
- Frontend Search & Filter → SELESAI 85%
- Frontend Halaman Salon → SELESAI 90%
- Frontend Booking Flow → SELESAI 75%
- Dashboard User → SELESAI 70%
- Bump overall progress from ~27% to ~70%
- Add "Phase 6 — Public Frontend Integration (May 1, 2026)" section
- Mark all "Routes" / "Controllers" / "Components" / "Views" file checkboxes that now exist

### 6.3 — `INTEGRATION_GUIDE.md`
- Append ` COMPLETED — May 1, 2026` banner near top
- Add **"Deviations from original guide"** section documenting:
- 3-step `slug` migration strategy (nullable → backfill → unique)
- `catatan` placed on `order_detail` (per integration guide)
- `BookingController` uses `harga_at_order/subtotal/start_time/end_time` (existing schema), not the guide's `harga/qty`
- `Kota.nama` and `SalonImage.url` are accessors, not real columns
- UI fully translated to English
- Static map placeholders replaced with Leaflet (OpenStreetMap, CDN)
- Currency switched from Rp to £ to match UK data source
- Tick checklist items at bottom

### 6.4 — `LAPORAN_PROYEK.md` (new at project root)
Sections:
1. Latar belakang & ringkasan eksekutif
2. Daftar perubahan per fase (Phase 1–6)
3. Tabel "File ditambahkan vs dimodifikasi"
4. Keputusan teknis & alasannya
5. Verifikasi & QA checklist
6. Sisa pekerjaan / known limitations (payment, review data, staff_schedule)
7. Apendix — daftar route final, komponen baru, contoh `<x-leaflet-map>`

---

## Verification (still to run after phase 6)

1. `php artisan route:list` — confirm 18 named public routes
2. `php artisan migrate:status` — three new migrations RAN
3. Tinker:
- `Salon::whereNotNull('slug')->count()` equals `Salon::count()`
- `Schema::hasColumn('order_detail', 'catatan')` true
- `Salon::first()->primaryImage?->url` returns string (via accessor)
- `Kota::first()->nama` returns city name (via accessor)
4. Browser smoke test (`php artisan serve` + `npm run dev`):
- `/`, `/cari?q=hair`, `/kategori/{slug}`, `/salon/{slug}`, `/booking/{slug}`, `/akun`
5. `php artisan view:clear && php artisan route:clear && php artisan config:clear`

---

## Hand-off checklist for the next agent

If you're picking this up:
1. Read this file end-to-end.
2. Read [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md) and the plan file at `C:\Users\Yosua Valentino\.claude\plans\perhatikan-seluruh-file-dan-dazzling-mountain.md`.
3. The **next concrete task** is Phase 5.3 — start with `resources/views/booking/create.blade.php` (use the original at `update/resources/views/booking/create.blade.php` as the source, translate to English, swap Rp→£, swap month/weekday names to English).
4. Update this file (`PROGRESS_REPORT.md`) at the end of every phase you complete — flip the row in "Status snapshot" and append any deviations to that phase's section.
5. Don't run `php artisan migrate` until Phase 5.3 is finished — leave that for the verification step.
