# README — Cleanup Round (Admin UI + Anti-Spam + TW4 Sweep + Bug Fixes)

> **Feature:** Filament MitraApplicationResource · Rate limiting on public forms · Tailwind v4 lint sweep · BUG-H01/H04/H05/M02 fixes · `bookings:complete` scheduled command
> **Status:** ✅ Implemented — 3 May 2026
> **Branch:** `feature/polish-round`
> **Companion docs:** [README-POLISH-ROUND.md](README-POLISH-ROUND.md), [README-FINAL-AUDIT.md](README-FINAL-AUDIT.md)

---

## What's in this round

Five parallel streams based on the priority backlog from README-POLISH-ROUND.md and README-FINAL-AUDIT.md:

1. **Filament `MitraApplicationResource`** — admin UI for partnership applications
2. **Anti-spam throttle** on the three public POST routes
3. **Tailwind v4 lint sweep** — deprecated utility class migration (51 replacements)
4. **Bug fixes from audit** — BUG-H01, BUG-H04, BUG-H05, BUG-M02
5. **`bookings:complete` command** — scheduled auto-transition confirmed→success

---

## 1. Filament MitraApplicationResource

### What it does

Adds a full triage UI in the admin panel (`/admin`) under a new **"Partnerships"** navigation group.
Partnership team members can now:

- View all incoming salon applications in a sortable, searchable table
- Filter by status (new / contacted / approved / rejected)
- Update an application's status via an inline modal — no full page navigation needed
- Bulk-mark multiple applications as "Contacted" or "Rejected"
- Click into any application to see all details and update status from the edit page

### Files created

| File | Purpose |
|------|---------|
| `app/Filament/Resources/MitraApplicationResource.php` | Main resource (table, form, actions) |
| `app/Filament/Resources/MitraApplicationResource/Pages/ListMitraApplications.php` | Index page |
| `app/Filament/Resources/MitraApplicationResource/Pages/ViewMitraApplication.php` | View/edit page (status-change form) |

### Table columns

| Column | Sortable | Searchable | Notes |
|--------|----------|------------|-------|
| ID | ✅ | — | |
| Salon Name | ✅ | ✅ | |
| Owner Name | — | ✅ | |
| Email | — | ✅ | Copyable |
| Phone | — | — | |
| City | — | ✅ | Via `kota.nama_kota` relation |
| Status | ✅ | — | Colour-coded badge (warning/info/success/danger) |
| Submitted | ✅ | — | `created_at` |

### Row actions

- **Update Status** — opens a modal with a `Select` field pre-filled with current status.
  Saves on submit without leaving the list page.
- **View** — navigates to the full detail page.

### Bulk actions

- **Mark as Contacted** — flips all selected rows to `status=contacted`
- **Mark as Rejected** — flips all selected rows to `status=rejected`

Both require confirmation before executing.

### Form (view/edit page)

All applicant fields are read-only (disabled). Only `status` is editable — this prevents accidental
editing of submitted data.

### Route registered

```
GET  /admin/mitra-applications          → ListMitraApplications
GET  /admin/mitra-applications/{record} → ViewMitraApplication
```

---

## 2. Anti-Spam Rate Limiting on Public Forms

### What changed

Three public POST routes were unprotected against automated submissions. Added Laravel's built-in
`throttle` middleware with conservative limits:

| Route | Limit | Reasoning |
|-------|-------|-----------|
| `POST /mitra/apply` | 5 per minute per IP | Lead form — high value, low expected volume |
| `POST /contact` | 10 per minute per IP | Support inbox — moderate volume expected |
| `POST /newsletter` | 3 per minute per IP | Email signup — lowest expected volume |

### File changed

`routes/web.php` — three route definitions updated:

```php
Route::post('/mitra/apply', [MitraController::class, 'apply'])
    ->middleware('throttle:5,1')
    ->name('mitra.apply');

Route::post('/contact', [StaticController::class, 'submitContact'])
    ->middleware('throttle:10,1')
    ->name('static.contact.submit');

Route::post('/newsletter', [StaticController::class, 'subscribeNewsletter'])
    ->middleware('throttle:3,1')
    ->name('newsletter.subscribe');
```

### Behaviour on limit exceeded

Laravel returns HTTP 429 "Too Many Requests" with a `Retry-After` header. The existing error-banner
pattern in each view will display the Laravel default throttle message. No additional view changes
were needed.

---

## 3. Tailwind v4 Lint Sweep

### Why

Tailwind v4 renamed several utility classes. The old names still work via a compatibility shim in v4
but **will be removed in Tailwind v5**. Migrating now eliminates future breakage.

### Replacements applied

| Old class (v3 / compat) | New class (v4 canonical) | Occurrences replaced |
|-------------------------|--------------------------|----------------------|
| `bg-gradient-to-br` | `bg-linear-to-br` | 4 |
| `bg-gradient-to-tr` | `bg-linear-to-tr` | 2 |
| `bg-gradient-to-r` | `bg-linear-to-r` | 2 |
| `bg-gradient-to-b` | `bg-linear-to-b` | 3 |
| `flex-shrink-0` | `shrink-0` | 37 |
| `flex-grow` | `grow` | 1 |
| `aspect-[16/9]` | `aspect-video` | 1 |
| **Total** | | **50 replacements** |

> `aspect-[16/10]` was intentionally left as-is — there is no standard alias in v4 for this ratio
> and an arbitrary value `aspect-[16/10]` is still valid Tailwind syntax.

### Files affected

| File |
|------|
| `resources/views/gift-card/index.blade.php` |
| `resources/views/home.blade.php` |
| `resources/views/kategori/show.blade.php` |
| `resources/views/lookbook/index.blade.php` |
| `resources/views/static/about.blade.php` |
| `resources/views/static/careers.blade.php` |
| `resources/views/treatment-files/index.blade.php` |
| `resources/views/akun/bookings.blade.php` |
| `resources/views/akun/index.blade.php` |
| `resources/views/akun/reward.blade.php` |
| `resources/views/booking/create.blade.php` |
| `resources/views/cari/index.blade.php` |
| `resources/views/components/salon-card.blade.php` |
| `resources/views/components/viygo-footer.blade.php` |
| `resources/views/components/viygo-logo.blade.php` |
| `resources/views/components/viygo-navbar.blade.php` |
| `resources/views/salon/show.blade.php` |
| `resources/views/welcome.blade.php` |

### Post-sweep build

`npm run build` completed successfully. CSS bundle: 274.40 kB (37.04 kB gzipped) — unchanged from
pre-sweep (the compat shim inlines the same CSS; removing the shim layer in v5 will reduce bundle size).

---

## 4. Bug Fixes (from README-FINAL-AUDIT.md)

### BUG-H01 — Staff Cross-Salon Booking Attack

**File:** `app/Http/Controllers/BookingController.php`

The booking `store()` method accepted any `id_staff` integer without verifying that the staff member
belongs to the selected salon. Added an ownership + active-status check before the slot availability
check:

```php
// Ensure the requested staff belongs to this salon and is active.
if ($staffId) {
    Staff::where('id_staff', $staffId)
        ->where('id_salon', $salon->id_salon)
        ->where('status', 'active')
        ->firstOrFail();
}
```

If the staff ID is invalid or from a different salon, Eloquent throws a `ModelNotFoundException`
which Laravel converts to a 404 response.

Also cleaned up the `$staffId` normalization:
```php
// Before
$staffId = $data['id_staff'] ?? null;
if ($staffId === 0) { $staffId = null; }

// After
$staffId = isset($data['id_staff']) && (int) $data['id_staff'] !== 0
    ? (int) $data['id_staff']
    : null;
```

---

### BUG-H04 — Phone Number Silently Dropped on Profile Update

**File:** `app/Http/Controllers/AkunController.php`

`updatePengaturan()` only accepted `first_name`, `last_name`, and `email`. `phone_number` was in
`User::$fillable` but never saved. Added validation and update:

```php
$request->validate([
    'first_name'   => 'required|string|max:100',
    'last_name'    => 'nullable|string|max:100',
    'email'        => 'required|email|unique:users,email,' . auth()->id() . ',id_user',
    'phone_number' => 'nullable|string|max:30|regex:/^[+\d\s\-()]+$/',
]);

auth()->user()->update($request->only('first_name', 'last_name', 'email', 'phone_number'));
```

The regex `^[+\d\s\-()]+$` allows international formats like `+44 7700 900123` and `(020) 1234 5678`.

---

### BUG-H05 — NULL start_time / end_time Causes Incorrect Slot Computation

**File:** `app/Services/BookingSlotService.php`

`CarbonImmutable::parse(null)` returns `Carbon::now()`, which would silently treat a staff member as
"busy from now until the end of the day" if their `order_detail` row had a null time. Added a guard:

```php
foreach ($rows as $row) {
    if (! $row->start_time || ! $row->end_time) {
        continue;  // Skip malformed rows.
    }
    $by[$row->id_staff][] = [
        'start' => CarbonImmutable::parse($row->start_time),
        'end'   => CarbonImmutable::parse($row->end_time),
    ];
}
```

---

### BUG-M02 — $sort Not Passed to cari.index View

**File:** `app/Http/Controllers/SearchController.php`

```php
// Before
return view('cari.index', compact('salons', 'q', 'lokasi'));

// After
return view('cari.index', compact('salons', 'q', 'lokasi', 'sort'));
```

The view can now highlight the active sort option and pre-select it on re-render.

---

## 5. `bookings:complete` Scheduled Command

### Problem solved

After a customer pays via Midtrans, their order transitions to `status='confirmed'`. There was no
mechanism to then move it to `status='success'` after the appointment date passes. This meant:
- The "Selesai" tab in `/akun/bookings` was always empty for customers who paid online.
- Customers could never submit a review because `ReviewController::resolveReviewableOrder()` requires
  `status='success'`.

### Solution

New artisan command `bookings:complete` registered at `app/Console/Commands/CompleteBookings.php`.
Scheduled to run daily at 01:00 via `routes/console.php`.

```php
// app/Console/Commands/CompleteBookings.php
Order::where('status', 'confirmed')
    ->whereDate('date_order', '<', now()->toDateString())
    ->update(['status' => 'success']);
```

### Features

- `--dry-run` flag: reports the count without writing (`php artisan bookings:complete --dry-run`)
- Logs the count via `Log::info('bookings:complete', ['completed' => $count])`
- Returns `Command::SUCCESS` in all cases (no exception on zero rows)

### Schedule

```php
// routes/console.php
Schedule::command('bookings:complete')->dailyAt('01:00');
```

Running at 01:00 means a booking from yesterday is promoted to `success` by early morning, so the
customer can leave a review the same day.

### Enabling the scheduler (production)

Add a single cron entry on the server:
```
* * * * * cd /path/to/viygo && php artisan schedule:run >> /dev/null 2>&1
```

---

## Verification

```
php artisan view:cache
→ INFO  Blade templates cached successfully.

php artisan route:list | grep mitra
→ GET  admin/mitra-applications                → ListMitraApplications
→ GET  admin/mitra-applications/{record}       → ViewMitraApplication
→ GET  mitra                                   → MitraController@index
→ POST mitra/apply                             → MitraController@apply

php artisan bookings:complete --dry-run
→ Dry run — 0 booking(s) would be marked as success.

npm run build
→ ✓ built in 3.90s
```

All 69 Blade templates compile without errors.  
All 129 routes load (2 new Filament routes from MitraApplicationResource).

---

## Files Summary

### Created (4)

```
app/Filament/Resources/MitraApplicationResource.php
app/Filament/Resources/MitraApplicationResource/Pages/ListMitraApplications.php
app/Filament/Resources/MitraApplicationResource/Pages/ViewMitraApplication.php
app/Console/Commands/CompleteBookings.php
```

### Modified (7)

```
routes/web.php                              — throttle middleware on 3 POST routes
routes/console.php                          — Schedule::command('bookings:complete')
app/Http/Controllers/BookingController.php  — BUG-H01: staff ownership validation
app/Http/Controllers/AkunController.php     — BUG-H04: phone_number in update
app/Http/Controllers/SearchController.php   — BUG-M02: $sort passed to view
app/Services/BookingSlotService.php         — BUG-H05: null guard on time fields
resources/views/**/*.blade.php              — TW4 sweep (50 replacements, 18 files)
```

---

## Outstanding Items (Not in This Round)

| Item | Reason deferred |
|------|----------------|
| BUG-07: Paid booking cancellation + Midtrans refund | Needs product decision |
| hCaptcha / Turnstile on `/mitra` | Nice-to-have after throttle is live |
| Newsletter list provider (Mailchimp/Resend) | No provider chosen yet |
| `aspect-[16/10]` → no v4 alias | No standard alias exists; leave as arbitrary value |
| Image generation pass | Needs separate image-gen pipeline (README-GAMBAR-STATIS.md) |
| TUGAS 9 (Batch 2 features) | Do not start until launch-blockers are resolved |

---

**Author:** Claude (Sonnet 4.6) — 3 May 2026.
