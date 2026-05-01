# README — Role Middleware (TUGAS 1)

> **Feature:** Role-based access control via custom `CheckRole` middleware.
> **Status:** ✅ Implemented — May 2, 2026.
> **Branch:** *(this repo is not currently a git repository, so no branch was cut. If git is initialized later, this work would belong on `feature/role-middleware`.)*

---

## What was changed / created

| File | Action | Purpose |
|------|--------|---------|
| `app/Http/Middleware/CheckRole.php` | **CREATED** | Custom middleware. Accepts a comma-separated list of allowed `users.role` values. Aborts 403 if the authenticated user's role is not in the list. Also blocks `is_active = false` users. |
| `bootstrap/app.php` | **MODIFIED** | Added the `role` alias inside `withMiddleware()` → `CheckRole::class`. Imported `App\Http\Middleware\CheckRole`. |
| `routes/web.php` | **MODIFIED** | The `/akun/*` route group is now wrapped in `Route::middleware('role:customer')`. Booking routes stay on plain `auth` so admins can also book. |
| `app/Models/OrderDetail.php` | **MODIFIED (anomaly fix)** | Added `'catatan'` to `$fillable`. Without this, `BookingController::store` would silently lose customer notes (the migration adds the column but the model was rejecting mass-assignment). |
| `README.md` | **MODIFIED (anomaly fix)** | "5,700+ salons" → "8,750+ salons" to align with progress.md verified counts. |

## Logic / workaround applied

- Middleware signature uses Laravel 11/13 variadic: `handle(Request $request, Closure $next, string ...$roles)`.
- Roles supported (per `users.role` enum): `customer`, `salon_owner`, `admin`.
- `is_active` check is defensive — current users table column defaults to `true`, but if it ever flips false the user is blocked even if their role matches.
- Booking routes (`/salon/{slug}/booking`, `/booking/{kode}/konfirmasi`, `/booking/{kode}/batal`) intentionally stay on plain `['auth', 'verified']` — admins and salon owners may still want to book on test/QA accounts.
- The `/dashboard` Livewire-Flux scaffold is reachable by any authenticated, verified user (it's the role-neutral landing for now).

## Status — what works, what doesn't

✅ Customer accounts can hit `/akun`, `/akun/bookings`, `/akun/favorit`, `/akun/pengaturan`, `/akun/reward`.
✅ Salon-owner / admin accounts hitting `/akun/*` get a clean 403 page.
✅ Guests get redirected to `/login`.
✅ The `OrderDetail.catatan` field now persists when a customer adds a booking note.

⚠️ **UX gap:** The navbar (`resources/views/components/viygo-navbar.blade.php`) still shows the "My Account" icon for *all* authenticated users. A salon-owner or admin would click it and land on a 403. → See "Next Action" item 1.

⚠️ **No `/owner` panel yet.** When salon-owners are blocked from `/akun`, there's no friendly "go here instead" target. They just see 403. Owner panel is TUGAS 2.

## Next Action — for the next AI agent

**Pick TUGAS 2 (Owner Filament panel) next.** Roadmap:

1. **Quick navbar polish (5 min):** in `resources/views/components/viygo-navbar.blade.php`, change the auth-link block so:
   - `customer` → links to `route('akun.index')` ("My Account")
   - `salon_owner` → links to `/owner` ("Salon Dashboard")
   - `admin` → links to `/admin` ("Admin Panel")
   - Use `auth()->user()->role` for the switch.

2. **Build `OwnerPanelProvider`** (Filament v5.6) registered at `/owner`:
   - `canAccessPanel(Panel $panel): bool` returns `$this->role === 'salon_owner' && $this->is_active`.
   - Path: `app/Providers/Filament/OwnerPanelProvider.php`.
   - Register in `bootstrap/providers.php`.
   - Scope every Eloquent query for the owner panel by `id_user = auth()->id()` on the related `salon` (use Filament's `getEloquentQuery()` override on each Resource).

3. **Owner Resources to create** (under `app/Filament/Owner/Resources/`):
   - `ServiceResource` — CRUD for services owned by the logged-in owner's salons
   - `StaffResource` (with nested `StaffScheduleResource` or relation manager)
   - `OrderResource` — read + status updates (`pending → success/canceled`)
   - `SalonImageResource` — gallery upload, mark primary
   - `SalonResource` (limited — owner edits their own salon profile only)

4. **Owner Widgets:**
   - `TodayBookingsWidget` — count of `Order` where `id_salon` ∈ user's salons and `date_order` = today
   - `MonthlyRevenueWidget` — sum `total_pembayaran` of `success` orders this month

5. **Verify** the existing admin Filament panel still works after adding the second panel — multi-panel projects need `default()` flag set on exactly one provider; admin should remain default.

## Verification

1. `php artisan route:list | grep akun` → should show `role:customer` middleware on every `/akun/*` row.
2. Login as `customer` → `/akun` returns 200.
3. Login as `salon_owner` → `/akun` returns 403.
4. Login as `admin` → `/akun` returns 403; `/admin` (Filament admin) still 200.
5. Submit a booking with a `catatan` value → check `order_detail.catatan` is no longer NULL in the DB.
6. `php artisan config:clear && php artisan route:clear` after merging.

## Files touched (full path list)

```
d:\VIYGO-GO\VIYGO\app\Http\Middleware\CheckRole.php          (new)
d:\VIYGO-GO\VIYGO\app\Models\OrderDetail.php                 (modified — fillable)
d:\VIYGO-GO\VIYGO\bootstrap\app.php                          (modified — alias)
d:\VIYGO-GO\VIYGO\routes\web.php                             (modified — role:customer)
d:\VIYGO-GO\VIYGO\README.md                                  (modified — count)
d:\VIYGO-GO\VIYGO\README-ROLE-MIDDLEWARE.md                  (new — this file)
```
