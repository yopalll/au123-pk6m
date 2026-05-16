# VIYGO — AI Agent System Guide

> **Version:** 1.0 | **Updated:** 2026-04-28
> **Purpose:** This file is the authoritative system instruction for any AI agent (LLM) assigned to develop the VIYGO project. Read this file in full before executing any task.

---

## 1. PROJECT MISSION

VIYGO is a **full-stack web application** that replicates the core functionality of [Treatwell.co.uk](https://www.treatwell.co.uk) — a beauty & wellness booking platform. The goal is to build a production-quality clone where users can:

- Search salons by location, category, and service
- Book appointment slots with specific staff
- Leave reviews after completed services
- Manage salon profiles (salon owner role)
- Administer the entire platform (admin role)

**Data foundation is complete:** 5,767 salons, 190,594 services, 7,568 staff, and 50,492 salon images are already seeded into the database. The primary remaining work is **backend logic and frontend UI**.

---

## 2. TECHNICAL ARCHITECTURE

### 2.1 Stack Overview

| Layer | Technology |
|--------------------|-------------------------------------|
| **Backend** | Laravel 13 (PHP ^8.3) |
| **Frontend / UI** | Livewire Flux v2 + TailwindCSS v4 |
| **Auth** | Laravel Fortify (2FA-ready) |
| **Database** | MySQL (`viygo-go`) |
| **Scraper** | Go (Golang) — separate repo |
| **Testing** | PestPHP v4 |
| **Build Tool** | Vite + npm |

### 2.2 Database Schema (Canonical Table List)

All tables and their relationships are **finalized and migrated**. Do not alter schema unless explicitly instructed.

```
kota → id_kota (PK), nama_kota, slug
kategori → id_kategori (PK), nama, slug, is_active
users → id_user (PK), role [customer|salon_owner|admin], SoftDeletes
salon → id_salon (PK), id_user (FK), id_kota (FK), status, lat, long, SoftDeletes
service → id_service (PK), id_salon (FK), id_kategori (FK), harga, durasi, SoftDeletes
staff → id_staff (PK), id_salon (FK), SoftDeletes
staff_service → pivot (id_staff, id_service)
staff_schedule→ id_schedule (PK), id_staff (FK), hari, jam_mulai, jam_selesai
salon_images → id_image (PK), id_salon (FK), image_url, is_primary
promo → id_promo (PK), SoftDeletes
order → id_order (PK), id_user (FK), id_salon (FK), id_promo (FK nullable)
order_detail → id_detail (PK), id_order (FK), id_service (FK), id_staff (FK nullable)
pembayaran → id_pembayaran (PK), id_order (FK), id_user (FK), status
review → id_review (PK), id_user (FK), id_salon (FK), id_order (FK), is_visible
user_promo → pivot (id_user, id_promo)
```

### 2.3 Eloquent Models (All Complete — Do Not Recreate)

All 13 models are **implemented and verified**. Location: `app/Models/`

```
User, Kota, Kategori, Salon, Promo, Service, Staff,
Order, Review, SalonImage, StaffSchedule, OrderDetail, Pembayaran
```

Key model conventions:
- **Custom PKs**: All models use non-default primary keys (e.g., `id_salon`, `id_service`). Always check `$primaryKey` before querying.
- **SoftDeletes**: Applied on `User`, `Salon`, `Service`, `Staff`, `Promo`. Always use `->withTrashed()` when needed.
- **Scopes available**: `Salon::active()`, `Salon::byKota()`, `Kategori::active()`, `Review::visible()`, `Pembayaran::completed()`, `Pembayaran::pending()`, `Promo::active()`.
- **Primary image**: Use `$salon->primaryImage` to get the main photo (returns `SalonImage` or `null`).

### 2.4 Current Route State

```php
// routes/web.php — Current (minimal):
Route::view('/', 'welcome')->name('home');
Route::view('dashboard', 'dashboard')->name('dashboard');
```

All new routes must be added to `routes/web.php`. Follow Livewire full-page component pattern.

---

## 3. DEVELOPMENT PRIORITIES

Work in strict priority order. Do not skip to a lower priority unless the higher one is complete.

| Priority | Feature | Status |
|----------|--------------------------|------------|
| P1 | Landing Page | NOT STARTED |
| P2 | Search & Filter | NOT STARTED |
| P3 | Salon Detail Page | NOT STARTED |
| P4 | Booking Flow (wizard) | NOT STARTED |
| P5 | User Dashboard | NOT STARTED |
| P6 | Salon Owner Dashboard | NOT STARTED |
| P7 | Admin Panel | NOT STARTED |
| P8 | Role Middleware | NOT STARTED |
| P9 | Auth (login/register) | 30% Done |

### Key Files To Create (by priority)

**P1 — Landing Page:**
```
resources/views/welcome.blade.php ← REPLACE DEFAULT LARAVEL PAGE
resources/views/components/navbar.blade.php
resources/views/components/footer.blade.php
resources/views/components/salon-card.blade.php
resources/views/components/category-card.blade.php
app/Livewire/HomePage.php
resources/views/livewire/home-page.blade.php
```

**P2 — Search:**
```
app/Livewire/SalonSearch.php
resources/views/livewire/salon-search.blade.php
```

**P3 — Salon Detail:**
```
app/Livewire/SalonDetail.php
resources/views/livewire/salon-detail.blade.php
resources/views/components/service-list.blade.php
resources/views/components/staff-card.blade.php
resources/views/components/image-gallery.blade.php
resources/views/components/review-card.blade.php
```

**P4 — Booking:**
```
app/Livewire/Booking/StepService.php
app/Livewire/Booking/StepStaff.php
app/Livewire/Booking/StepDateTime.php
app/Livewire/Booking/StepConfirm.php
resources/views/livewire/booking/*.blade.php
app/Http/Controllers/OrderController.php
```

---

## 4. CODING STANDARDS

### 4.1 Language Rules

- **All function/method names**: English only.
- **Variable names**: English only.
- **Blade view comments and docblocks**: English.
- **User-facing text (labels, headings)**: Indonesian (to match local UX context).
- **Database column names**: As-is from schema (Indonesian abbreviations like `nama_kota`, `id_user` are canonical — do not rename).

### 4.2 Laravel Conventions

- **Livewire components** are preferred over traditional controllers for UI-heavy pages.
- Always use **Eloquent relationships** instead of raw DB queries.
- Always apply **eager loading** (`with()`) to prevent N+1 issues:
```php
// Correct:
Salon::with(['kota', 'primaryImage', 'services'])->active()->paginate(12);

// Wrong:
Salon::all(); // then $salon->kota in a loop
```
- Use **Query Scopes** from the models rather than rewriting `where()` chains.
- **Pagination**: Use `paginate(12)` or `paginate(24)` for all list views.
- **Form validation**: Use Laravel Form Request classes for any POST/PUT endpoints.

### 4.3 Livewire Standards

- Declare properties with PHP 8.3 typed attributes.
- Use `#[Url]` for query string params (search, filters).
- Use `#[Computed]` for derived data used in templates.
- Use `wire:navigate` for SPA-like navigation between Livewire full-page components.

### 4.4 Blade / Frontend

- All components go in `resources/views/components/` and use anonymous component syntax `<x-component-name />`.
- TailwindCSS v4 is the **only** CSS framework. Do not add custom CSS files unless absolutely necessary.
- Respect Livewire Flux v2 component patterns (e.g., `<flux:button>`, `<flux:input>`).

### 4.5 Security Rules

- Never expose `id_user`, `id_salon`, or other internal PKs in public URLs. Use slugs or UUIDs where possible.
- Always apply `auth` middleware on routes that require login.
- Never trust user input — validate everything with Form Requests or Livewire `#[Validate]`.

---

## 5. CRITICAL BUSINESS LOGIC

### 5.1 Booking Slot Availability Check

Before creating an `order_detail`, always verify no double-booking:

```php
$isAvailable = !OrderDetail::whereHas(
'order',
fn($q) => $q->where('date_order', $date)
->whereNotIn('status', ['cancelled'])
)
->where('id_staff', $staffId)
->where('start_time', '<', $endTime)
->where('end_time', '>', $startTime)
->exists();
```

### 5.2 Search Query Pattern (P2 Reference)

```php
Salon::with(['kota', 'primaryImage'])
->when($category, fn($q) =>
$q->whereHas('services.kategori', fn($q) =>
$q->where('slug', $category)))
->when($city, fn($q) =>
$q->whereHas('kota', fn($q) =>
$q->where('nama_kota', 'like', "%{$city}%")))
->when($minRating, fn($q) =>
$q->where('rating', '>=', $minRating))
->active()
->paginate(12);
```

### 5.3 Role Enforcement

Users have a `role` column with 3 possible values:

| Role | Access |
|---------------|----------------------------------------------|
| `customer` | Browse, book, review, own profile |
| `salon_owner` | + Manage own salon, staff, services |
| `admin` | + Full platform control |

Use `CheckRole` middleware (to be created at `app/Http/Middleware/CheckRole.php`) and register in `bootstrap/app.php`.

### 5.4 Promo Validation

When applying a promo code at checkout:
1. Check `promo.is_active = 1` AND `promo.end_date >= NOW()`.
2. Check the user has NOT already used this promo via the `user_promo` pivot.
3. Apply discount before creating the `pembayaran` record.

---

## 6. KNOWN ISSUES & CONSTRAINTS

| # | Issue | Impact |
|---|-------|--------|
| 1 | `welcome.blade.php` is default Laravel page | P1 blocker |
| 2 | No controllers exist (only base `Controller.php`) | P4 blocker |
| 3 | No Livewire components exist yet | P1–P5 blocker |
| 4 | `routes/web.php` has only 2 routes | All pages blocked |
| 5 | `staff_schedule` table has 0 records | Booking time-slot logic will return all slots as available — implement basic schedule generation or seed data first |
| 6 | `order`, `review`, `pembayaran` have 0 records | Normal; features build from scratch |
| 7 | `CheckRole` middleware not created | Auth gating not enforceable yet |

---

## 7. STRICT CONSTRAINTS

These rules are **non-negotiable**. An agent must not violate them under any circumstance:

1. **Do not modify migrations.** The schema is final. Create a new migration only if explicitly instructed by the developer.
2. **Do not recreate existing models.** All 13 Eloquent models are verified and complete. Only modify them if adding a missing relationship or scope.
3. **Do not rename database columns.** Column names in the schema are canonical.
4. **Do not install new Composer packages** without explicit developer approval.
5. **Do not break the existing seeder chain.** If touching `DatabaseSeeder.php`, preserve the FK-safe execution order: Kota → Kategori → User → Salon → Service → Staff → SalonImages.
6. **Always run `php artisan migrate:status`** to verify migration state before proposing schema changes.
7. **Do not hardcode credentials or secrets.** Always read from `.env` via `config()` helpers.
8. **Stay on branch `go-fresh`.** Do not switch or merge branches unless explicitly told to.

---

## 8. COMMUNICATION STYLE FOR AI AGENTS

When reporting progress, discoveries, or errors, follow this format:

- **Be technical and concise.** No filler language or extensive preamble.
- **Lead with the action taken**, then the result, then any blockers.
- **Use code blocks** for all code snippets, commands, and file paths.
- **Quantify when possible** (e.g., "Added 3 routes", "Query returns 12 rows in 42ms").
- **Flag blockers explicitly** with `[BLOCKED]` and state the dependency.
- **Never ask for confirmation** on standard Laravel tasks. Execute and report.
- If a decision is ambiguous, **pick the most conservative option** and document it.

### Example Agent Response Format

```
TASK: Implement SalonSearch Livewire component.
ACTION: Created `app/Livewire/SalonSearch.php` + `salon-search.blade.php`.
Added route `/search` to `routes/web.php`.
QUERY: Uses Salon::active()->with(['kota','primaryImage'])->paginate(12).
Supports filters: category (slug), city (LIKE), min_rating.
RESULT: Component renders correctly. Pagination works. N+1 eliminated.
NEXT: [P3] SalonDetail page — route `/salon/{id_salon}`.
```

---

## 9. PROJECT FILE MAP (Quick Reference)

```
VIYGO/
├── app/
│ ├── Http/
│ │ ├── Controllers/ # HomeController, OrderController (to create)
│ │ ├── Middleware/ # CheckRole (to create)
│ │ └── Requests/ # Form Request classes (to create)
│ ├── Livewire/
│ │ ├── HomePage.php # P1 — to create
│ │ ├── SalonSearch.php # P2 — to create
│ │ ├── SalonDetail.php # P3 — to create
│ │ └── Booking/ # P4 — to create (4 step classes)
│ └── Models/ # All 13 models complete — DO NOT RECREATE
│
├── database/
│ ├── data/ # JSON source files (read-only)
│ ├── migrations/ # Complete — DO NOT MODIFY
│ └── seeders/ # Complete — idempotent
│
├── resources/views/
│ ├── welcome.blade.php # Replace entirely (P1)
│ ├── components/ # Blade components (to create)
│ └── livewire/ # Livewire views (to create)
│
├── routes/
│ └── web.php # Add all new routes here
│
├── progress.md # Human-readable task tracker — update after each session
├── README.md # Project overview
└── AGENT_GUIDE.md # ← THIS FILE
```

---

## 10. HOW TO UPDATE PROGRESS

After completing any task, update `progress.md`:

1. Move the completed item from ` YANG PERLU DIKERJAKAN` to ` SUDAH SELESAI`.
2. Update the percentage estimate in the summary table.
3. Add a timestamped entry at the top: `> **Update terakhir:** DD Month YYYY — HH:MM WIB`.
4. Document any new known issues discovered during implementation.

---

*End of AGENT_GUIDE.md — This document is the single source of truth for all AI agents on the VIYGO project.*
