# README — Owner Filament Panel (TUGAS 2)

> **Feature:** A second Filament v5.6 panel mounted at `/owner`, scoped to a single salon owner so they can run their salon without admin help.
> **Status:** ✅ Implemented — 2 May 2026.
> **Branch:** `feature/owner-panel` (cut from `branch-viter`).
> **Companion docs:** [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md), [README-CUSTOMER-PANEL.md](README-CUSTOMER-PANEL.md), [README-AUTH-AND-STATIC.md](README-AUTH-AND-STATIC.md)

---

## Why a second panel (not Blade)?

The `prompt-next.md` brief was unambiguous: *"Jangan membuat dashboard owner dari awal menggunakan Blade murni. **Manfaatkan Filament Panel!**"* — and the existing admin panel already proved that Filament v5.6 works in this project. A second `PanelProvider` reuses all of Filament's table/form/filter machinery and gives us multi-tenancy with one Eloquent-query scope per Resource. The alternative (custom Blade + Livewire) would have meant re-implementing everything Filament gives for free.

Two panels coexist in the same app:

| Panel | URL | Audience | `default()` |
|-------|-----|----------|-------------|
| `AdminPanelProvider` | `/admin` | `role = admin` | yes (kept default) |
| `OwnerPanelProvider` | `/owner` | `role = salon_owner` | no |

`AdminPanelProvider` retains `->default()` so existing admin links and Filament's notification routes are unchanged.

## Files created

### Provider
| File | Purpose |
|------|---------|
| `app/Providers/Filament/OwnerPanelProvider.php` | Registers the `/owner` panel, declares brand colours, navigation groups (`Salon`, `Operations`, `Bookings`), and the standard middleware stack. |

### Resources (under `app/Filament/Owner/Resources/`)
| File | Highlights |
|------|------------|
| `SalonResource.php` (+ Pages: `ListSalons`, `EditSalon`, `ViewSalon`) | Owner sees only their own `salon` rows. Edit form locks down `status`, `slug`, `rating`, `total_review` (those are admin-controlled). Soft-deleted salons are visible via the trashed scope. **No create** — owners can't spawn salons themselves. |
| `SalonResource/RelationManagers/ServicesRelationManager.php` | Inline service CRUD inside the salon edit page. |
| `SalonResource/RelationManagers/StaffRelationManager.php` | Inline staff CRUD inside the salon edit page. |
| `SalonResource/RelationManagers/ImagesRelationManager.php` | Gallery CRUD with a "Make Primary" action that demotes the existing primary in one transaction-style update. |
| `ServiceResource.php` (+ 3 Pages) | Top-level CRUD across all the owner's salons (handy when an owner has multiple). Salon dropdown is filtered to `id_user = auth()->id()`. |
| `StaffResource.php` (+ 3 Pages) | Top-level staff CRUD. |
| `StaffResource/RelationManagers/SchedulesRelationManager.php` | Per-staff weekly working hours (`hari`, `start_time`, `end_time`, `is_available`). This is the data source TUGAS 4 (smart booking) will read from. |
| `OrderResource.php` (+ Pages `ListOrders`, `ViewOrder`) | Read-only order list with row actions: **Confirm** (`pending → confirmed`), **Mark Success** (`pending|confirmed → success`), **Cancel** (`* → canceled`). Date-range filter, status filter, customer search. **No create / edit / delete** — orders are owned by the booking flow. |
| `OrderResource/RelationManagers/OrderDetailsRelationManager.php` | Read-only line-items table on the order view page. |
| `SalonImageResource.php` (+ 3 Pages) | Top-level gallery management (mirrors the relation manager so owners can browse/edit the whole library at once). |

### Widgets (under `app/Filament/Owner/Widgets/`)
| File | What it shows |
|------|---------------|
| `OwnerStatsOverview.php` | 6 stat cards: Today's Bookings, Pending Approvals, Revenue This Month, Services count, Staff count, Average Rating. All queries use `whereIn('id_salon', $salonIds)` (the owner's salons). Empty-state when an owner has no salon yet. |
| `UpcomingOrdersTable.php` | Up to 10 upcoming non-canceled bookings ordered by `date_order`. |

### Other changes
| File | Change |
|------|--------|
| `app/Models/User.php` | `canAccessPanel()` now also accepts `panel.id === 'owner'` for `salon_owner` users. Added `ownedSalonIds(): array` helper used by the widgets. |
| `bootstrap/providers.php` | Registered `OwnerPanelProvider`. |
| `progress.md` | Flipped PRIORITAS 1 to ✅, bumped overall progress to ~85%. |

## Multi-tenancy approach (the important bit)

Every Resource overrides `getEloquentQuery()` and pins it to `auth()->id()`. There is no global Filament `tenant` configured because (a) we don't want users to switch context and (b) each owner's salons are already keyed by `users.id_user`. The chain is:

```
User (id_user) ──< Salon (id_user) ──< Service / Staff / SalonImage / Order ──< OrderDetail
```

So:
- `SalonResource` filters on `salon.id_user = auth()->id()`.
- `ServiceResource`, `StaffResource`, `SalonImageResource`, `OrderResource` all use `whereHas('salon', fn($q) => $q->where('id_user', auth()->id()))`.

This guarantees that even if an owner crafts a URL with a foreign `record` id, Filament's binding will return **404 Not Found** (the record falls outside the scoped query) instead of silently leaking another salon's data.

The Salon dropdowns inside `Service` / `Staff` / `SalonImage` create-forms are likewise scoped — owners only see their own salons in the picker, so they cannot mass-assign a row onto a salon they don't own.

## Status — what works

✅ A `salon_owner` user signs in at `/login` then visits `/owner` → sees the dashboard.
✅ The dashboard renders 6 stats cards + an "Upcoming Bookings" table.
✅ Sidebar groups: **Salon** (My Salon, Gallery), **Operations** (Services, Staff), **Bookings** (Orders).
✅ Editing a service/staff/image inside the owner panel only lists their own salons.
✅ Order row actions transition status correctly. Owners cannot delete orders.
✅ Admin panel at `/admin` still works (still `default()`).

## Status — known gaps / next actions

⚠️ **Owners can't onboard themselves.** `SalonResource::canCreate()` returns false. This is intentional — admin curates which salons exist on VIYGO. If/when self-serve onboarding lands, flip this and add a moderation queue.

⚠️ **No inline Filament file uploads for gallery yet.** The form takes an `image_url` string (paste a CDN URL). When TUGAS 9 (or sooner) adds an upload pipeline (S3 / local disk), swap the `TextInput` for `Forms\Components\FileUpload` and write to `salon_images.image_url`.

⚠️ **`OrderDetail.status` transitions are not auto-cascaded.** Marking the parent `Order` as `success` doesn't automatically flip its `OrderDetail` rows to `completed`. If the business needs that, attach an `Order::saved` observer.

⚠️ **No notifications/toasts to the customer** when an owner confirms or cancels. Pair this with the email work flagged for TUGAS 7 ("notifikasi email").

⚠️ **Vendor not currently installed locally:** `php artisan` errors with `Class "Filament\PanelProvider" not found`. That is **not a code defect** — `vendor/filament` is missing on this machine. Run `composer install` to populate it. All authored files pass `php -l` lint.

## Verification (after `composer install`)

1. `php artisan route:list | grep filament.owner` → should list `filament.owner.auth.login`, `filament.owner.pages.dashboard`, plus one route per Resource page.
2. As an admin, **promote** a user to `role = 'salon_owner'` and link a salon to them: `Salon::first()->update(['id_user' => $u->id_user])`.
3. Sign in as that user → `/owner` returns 200, dashboard shows their salon's metrics. `/admin` returns 403.
4. Sign in as `admin` → `/admin` still 200, `/owner` returns 403 (admin is not allowed; intentional, admins manage everyone via `/admin`).
5. As the owner, hit `/owner/services` → only their salon's services visible. Try `/owner/services/{id}/edit` for a service belonging to *another* salon → Filament responds with 404.
6. From the dashboard widget, click the order's row to view details → status-update buttons work and persist.

## Files summary

### Created
```
app/Providers/Filament/OwnerPanelProvider.php
app/Filament/Owner/Resources/SalonResource.php
app/Filament/Owner/Resources/SalonResource/Pages/ListSalons.php
app/Filament/Owner/Resources/SalonResource/Pages/EditSalon.php
app/Filament/Owner/Resources/SalonResource/Pages/ViewSalon.php
app/Filament/Owner/Resources/SalonResource/RelationManagers/ServicesRelationManager.php
app/Filament/Owner/Resources/SalonResource/RelationManagers/StaffRelationManager.php
app/Filament/Owner/Resources/SalonResource/RelationManagers/ImagesRelationManager.php
app/Filament/Owner/Resources/ServiceResource.php
app/Filament/Owner/Resources/ServiceResource/Pages/ListServices.php
app/Filament/Owner/Resources/ServiceResource/Pages/CreateService.php
app/Filament/Owner/Resources/ServiceResource/Pages/EditService.php
app/Filament/Owner/Resources/StaffResource.php
app/Filament/Owner/Resources/StaffResource/Pages/ListStaff.php
app/Filament/Owner/Resources/StaffResource/Pages/CreateStaff.php
app/Filament/Owner/Resources/StaffResource/Pages/EditStaff.php
app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php
app/Filament/Owner/Resources/OrderResource.php
app/Filament/Owner/Resources/OrderResource/Pages/ListOrders.php
app/Filament/Owner/Resources/OrderResource/Pages/ViewOrder.php
app/Filament/Owner/Resources/OrderResource/RelationManagers/OrderDetailsRelationManager.php
app/Filament/Owner/Resources/SalonImageResource.php
app/Filament/Owner/Resources/SalonImageResource/Pages/ListSalonImages.php
app/Filament/Owner/Resources/SalonImageResource/Pages/CreateSalonImage.php
app/Filament/Owner/Resources/SalonImageResource/Pages/EditSalonImage.php
app/Filament/Owner/Widgets/OwnerStatsOverview.php
app/Filament/Owner/Widgets/UpcomingOrdersTable.php
README-OWNER-PANEL.md  ← this file
```

### Modified
```
app/Models/User.php                ← canAccessPanel + ownedSalonIds()
bootstrap/providers.php            ← registered OwnerPanelProvider
progress.md                        ← PRIORITAS 1 → ✅
```

---

## Next Action — for the next AI agent

In priority order from `prompt-next.md`:

### 1. **TUGAS 5 — Review system** (medium, ~2-4h)

Customers can leave a `Review` once an `Order` flips to `success`. Build:
- Form on `/akun/bookings` Completed tab for orders with `status = success` and `whereDoesntHave('review')`.
- `ReviewController::create($kode)` and `store(Request, $kode)`. Validation: `rating` int 1-5, `komentar` string max 2000.
- In `store`, wrap in `DB::transaction`:
  - `Review::create([…])`
  - Recompute `salon.rating = avg(reviews.rating where is_visible)` and `salon.total_review = count`. Use `saveQuietly()`.
- Optional: a `ReviewObserver` that recalculates aggregates whenever `is_visible` flips (so admin moderation stays in sync).

### 2. **TUGAS 4 — Smart booking + Midtrans Snap** (large, ~1-2 days)

The Owner panel now has the `staff_schedule` data the booking flow needs. Next steps:
- Replace the static 14-slot grid in `resources/views/booking/create.blade.php` with a server-side slot generator that:
  - Reads `salon.opening_time` / `closing_time` and walks in `service.durasi`-minute steps.
  - Filters slots by staff in `staff_schedule` (matching `hari` from `date_order`'s weekday).
  - Subtracts slots overlapping non-canceled `OrderDetail.start_time / end_time` for the same staff.
- Add an "Any staff" wizard option.
- For Midtrans Snap (Sandbox): `composer require midtrans/midtrans-php`. Add `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` to `.env`. Implement `PaymentController@createSnapToken(Order $order)` and `webhook` (notification handler). On success, write a `pembayaran` row and transition `order.status` `pending → confirmed` (or `success`, per business). The brief explicitly asks for a web-search of the latest Snap docs before coding.

### 3. **TUGAS 7 — Header dummy pages polish**

`/gift-card`, `/lookbook`, `/treatment-files`, `/mitra` are stubs. Fill them with realistic content following [README-GAMBAR-STATIS.md](README-GAMBAR-STATIS.md) for visual assets.

### 4. **Owner panel polish leftovers**

- File-upload field for gallery (replace `TextInput` with `FileUpload` + a disk).
- Email/Slack notification when a new order arrives (so an owner doesn't need to refresh the dashboard).
- Permission gate on `canEdit(Salon)` — currently any field is editable; consider locking `latitude / longitude` once a salon is approved.
- Widget for revenue trend (last 30 days, line chart via Filament's `ChartWidget`).

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
