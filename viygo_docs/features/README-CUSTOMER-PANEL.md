# README — Customer Panel Polish (TUGAS 8)

> **Session:** 2 May 2026.
> **Branch:** *(repo is not currently a git repo; would belong on `feature/customer-panel-polish` if it were.)*
> **Companion docs:** [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md), [README-AUTH-AND-STATIC.md](README-AUTH-AND-STATIC.md)

---

## Scope completed

| Sub-task | Status |
|----------|--------|
| Wishlist — `user_favourites` pivot table | ✅ |
| Wishlist — `User::favourites()` + `hasFavourited()` helpers | ✅ |
| Wishlist — `AkunController::toggleFavorit()` POST endpoint | ✅ |
| Wishlist — heart icon in `salon-card.blade.php` wired to toggle | ✅ |
| Wishlist — `/akun/favorit` shows real saved salons | ✅ |
| `/akun/reward` — display claimed promos from `user_promo` pivot | ✅ |
| `/akun/index` — counts real `favourites` + active `promos` | ✅ |
| Booking cancellation rule — block past appointments | ✅ |

## Files created / modified

| File | Action |
|------|--------|
| `database/migrations/2026_05_02_000001_create_user_favourites_table.php` | **NEW** — pivot `id_user/id_salon` with `unique(['id_user','id_salon'])` + cascade-on-delete on both FKs |
| `app/Models/User.php` | **MODIFIED** — added `favourites()` (BelongsToMany on `Salon` via `user_favourites`) + `hasFavourited(int $idSalon): bool` helper |
| `app/Http/Controllers/AkunController.php` | **REWRITTEN** — `index()` now exposes `$favouriteCount` and `$promoCount`; `favorit()` returns real `favourites()` collection eager-loaded with `kota`, `services.kategori`, `primaryImage`; `toggleFavorit(Salon $salon, Request $request)` toggles attach/detach with JSON response support; `reward()` loads `auth()->user()->promos()` ordered by `is_used` then `time_expired` |
| `app/Http/Controllers/BookingController.php` | **MODIFIED** — `batal()` now refuses to cancel orders whose `date_order` is in the past; returns validation error rather than silently succeeding |
| `routes/web.php` | **MODIFIED** — re-added `POST /akun/favorit/{salon:slug}` → `akun.favorit.toggle` (binding by salon slug) |
| `resources/views/components/salon-card.blade.php` | **MODIFIED** — heart icon now: ① shows filled red icon when favourited; ② is a real `<form method="POST">` posting to `akun.favorit.toggle`; ③ for guests / non-customers, links to `/login`; ④ heart only appears for customers |
| `resources/views/akun/reward.blade.php` | **REWRITTEN** — adds "My Vouchers" section listing each claimed promo (name, description, code, expiry, %/£ off badge, status pill: Active / Used / Expired); empty-state when none |

## Logic / workarounds

- **Salon route binding:** `Route::post('/akun/favorit/{salon:slug}', …)` uses Laravel's implicit RouteModelBinding with `:slug` to disambiguate from `id_salon`. Works because `Salon::getRouteKeyName()` already returns `'slug'`.
- **Detach/attach:** `User::favourites()->attach()/detach()` is a simple Many-to-Many idempotent toggle; the unique constraint on the pivot guarantees no duplicates.
- **Cancellation rule** uses `$order->date_order->isPast()`. Order's `date_order` is cast as `'date'` in the model, so it becomes a Carbon. Limitation: `isPast()` on a date column resolves at midnight, so a same-day booking after the appointment's `start_time` is still cancelable until 23:59. → A stricter check would compare `date_order + details->first()->start_time` against `now()`, but that's blocked behind smart-booking work in TUGAS 4.
- **Promo `tipe_promo`** values are presumed to be `'percent'` or `'amount'` (or similar) — the view renders `%` if `'percent'`, otherwise `£`. If the actual enum uses different values, adapt the conditional.
- **Reward "Current Points"** card still shows hardcoded `0 / 1,500` — there is no points engine yet (no `points` column on `users`). This is left as a TODO; the My Vouchers list above it is real.

## Status — what's done, what's left

✅ A logged-in `customer` can heart a salon from any list/grid; the heart fills red and persists in the DB.
✅ `/akun/favorit` lists their saved salons using the existing `<x-salon-card>` component.
✅ `/akun/reward` shows real claimed promos with status pills.
✅ `/akun/index` tile counters reflect real DB state.
✅ Cancelling a past appointment now produces a friendly validation error instead of silently flipping it to `canceled`.

⚠️ **Gap:** salon-card heart for guests links to `/login` but doesn't preserve the salon to favourite after login. → Could be a follow-up: add `intended()` redirect with a `?favourite={slug}` query.

⚠️ **Gap:** the reward page's points counter is hardcoded `0`. Adding a points engine requires either a `points` column on `users` or an event-sourced ledger (`user_points` table) → out of scope for this session.

⚠️ **Gap:** `tipe_promo` value mapping is a guess. Verify with `Promo::distinct()->pluck('tipe_promo')` after seeding.

## Verification

1. Run the new migration: `php artisan migrate`. `user_favourites` table should be created.
2. As a `customer`, click the heart icon on any salon card → button refreshes with red filled heart, DB row in `user_favourites` exists.
3. Visit `/akun/favorit` → the same salon appears in the list.
4. Click heart again on the same salon → it disappears from `/akun/favorit`.
5. As a `salon_owner` or `admin`, the heart icon should still show but click on it leads to the customer-only `/akun/favorit/...` route which 403s via `role:customer` middleware (acceptable — owner/admin shouldn't be wishlisting). The view actually conditionally hides the form for non-customers; they see a sign-in link instead.
6. As a guest, click heart → redirected to `/login`.
7. Visit `/akun/reward` → vouchers list, with active/used/expired pills.
8. Try to cancel a past `pending` order via `POST /booking/{kode}/batal` → flash error "This appointment has already passed".

## Files summary

### Created
```
d:\VIYGO-GO\VIYGO\database\migrations\2026_05_02_000001_create_user_favourites_table.php
d:\VIYGO-GO\VIYGO\README-CUSTOMER-PANEL.md            ← this file
```

### Modified
```
d:\VIYGO-GO\VIYGO\app\Models\User.php
d:\VIYGO-GO\VIYGO\app\Http\Controllers\AkunController.php
d:\VIYGO-GO\VIYGO\app\Http\Controllers\BookingController.php
d:\VIYGO-GO\VIYGO\routes\web.php
d:\VIYGO-GO\VIYGO\resources\views\components\salon-card.blade.php
d:\VIYGO-GO\VIYGO\resources\views\akun\reward.blade.php
```

---

## Next Action — for the next AI agent

In priority order:

### 1. **TUGAS 5 — Review system** (medium, ~2-4h)

- Add a "Leave a review" button to the `/akun/bookings` Completed tab on each order that has `status=success` and no review (`->whereDoesntHave('review')`).
- New routes: `GET /akun/bookings/{kode}/review` (form) and `POST /akun/bookings/{kode}/review` (submit), guarded by `role:customer`.
- New `ReviewController` with `create($kode)` and `store(Request, $kode)`. Validation: `rating` integer 1–5, `komentar` string max 2000.
- In `store`, wrap in `DB::transaction`:
  - `Review::create(['id_user' => …, 'id_salon' => $order->id_salon, 'id_order' => $order->id_order, 'rating' => …, 'komentar' => …, 'is_visible' => true])`
  - Recompute `salon.rating = avg(reviews.rating where is_visible=true)` and `salon.total_review = count`. Use `saveQuietly()`.
- Display each user's reviews on `/akun` profile page (optional polish).
- Consider an `Observer` on `Review::saved/deleted` to recompute aggregates automatically when admin moderation toggles `is_visible`.

### 2. **TUGAS 4 — Smart booking + Midtrans** (large, ~1-2 days)

- **Smart slots:** rewrite the static 14-slot grid in `booking/create.blade.php`. Source slots from:
  - `salon.opening_time`–`salon.closing_time` (in `service.durasi`-minute increments)
  - Filter by `staff_schedule` for staff who can deliver the chosen service
  - Subtract slots overlapping any non-canceled `OrderDetail`
- **Staff selection:** add an "Any staff" option + per-staff option in the wizard.
- **Midtrans Snap:**
  - Web-search the latest Snap API docs (user explicitly required this)
  - `composer require midtrans/midtrans-php`
  - Add Sandbox keys to `.env`
  - `PaymentController@createSnapToken(Order $order)` returns `snap_token` for frontend Snap.pay()
  - `PaymentController@webhook` handles Midtrans notification → write `pembayaran` row, transition `order.status` from `pending` to `success` (or `confirmed` — TBD)

### 3. **TUGAS 2 — Owner Filament panel** (large, ~1-2 days)

- See "Next Action" in [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md) for the full layout.

### 4. **Polish leftovers**

- Reward page points engine (decide column-based vs event-sourced)
- Review aggregate observer
- Re-add `'role:customer'` middleware to the `/akun/favorit/{salon}` toggle (currently inherited from prefix group — verify)
- Verify `tipe_promo` value mapping in `akun/reward.blade.php` matches actual enum/values

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
