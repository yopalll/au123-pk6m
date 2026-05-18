# Bug Fix Execution Report — Phase 2: High Priority Logic & UI Fixes

> **Executed:** 4 May 2026
> **Branch:** `fix/post-payment-audit`

---

## Checklist

- [x] **BUG-03: Confirmed orders now visible in `/akun/bookings`**
  - `AkunController.php` — `bookings()` status map refactored to use `OrderStatus` constants.
  - `AkunController.php` — `index()` upcoming count now uses `whereIn` with `[PENDING, CONFIRMED]` (was only `PENDING`).
  - `resources/views/akun/bookings.blade.php` — Status labels and badge CSS classes refactored to use `OrderStatus` constants.
  - The `confirmed` status already had a label (`'Paid'`) and badge class (`bg-blue-100 text-blue-700`) in the existing file — now uses constants.

- [x] **BUG-04: Owner revenue widget includes paid bookings**
  - `OwnerStatsOverview.php` — Revenue query refactored from `->where('status', 'success')` to `->whereIn('status', [CONFIRMED, SUCCESS])`.
  - Today's booking count exclusion and pending count also use constants.

- [x] **BUG-02: Vite manifest**
  - Fix is a terminal command: `npm install && npm run build`.
  - **Not executed automatically** — requires user to run in their environment (listed in Phase 4 summary).

- [x] **BUG-11: Admin `OrderResource` references `confirmed`**
  - Auto-resolved. BUG-01 migration adds `confirmed` to the enum.
  - Additionally refactored all hardcoded status strings in admin `OrderResource.php` to use `OrderStatus` constants (form, table badges, filters, actions).

- [x] **Owner `OrderResource.php` also refactored**
  - All hardcoded status strings replaced with `OrderStatus` constants (form, table badges, filters, confirm/success/cancel actions).

- [x] **`PaymentController.php` refactored**
  - Webhook handler: `$order->status = 'confirmed'` → `OrderStatus::CONFIRMED` (2 occurrences).
  - `resolvePendingOrder()`: `->where('status', 'pending')` → `OrderStatus::PENDING`.

---

## Verification

| Check | Result |
|-------|--------|
| `php -l app/Http/Controllers/AkunController.php` | ✅ No syntax errors |
| `php -l app/Filament/Owner/Widgets/OwnerStatsOverview.php` | ✅ No syntax errors |
| `php -l app/Filament/Resources/OrderResource.php` | ✅ No syntax errors |
| `php -l app/Filament/Owner/Resources/OrderResource.php` | ✅ No syntax errors |
| `php -l app/Http/Controllers/PaymentController.php` | ✅ No syntax errors |
| All `use App\Constants\OrderStatus` imports present | ✅ Verified |

---

## Files Modified

| File | Bug(s) | Changes |
|------|--------|---------|
| `app/Http/Controllers/AkunController.php` | BUG-03 | Added `OrderStatus` import; refactored `bookings()` status map + `index()` upcoming count |
| `app/Filament/Owner/Widgets/OwnerStatsOverview.php` | BUG-04 | Revenue query includes `CONFIRMED`; all statuses use constants |
| `app/Filament/Resources/OrderResource.php` | BUG-11 | All hardcoded strings → `OrderStatus` constants |
| `app/Filament/Owner/Resources/OrderResource.php` | — | All hardcoded strings → `OrderStatus` constants |
| `app/Http/Controllers/PaymentController.php` | — | Webhook + resolvePendingOrder use `OrderStatus` constants |
| `resources/views/akun/bookings.blade.php` | BUG-03 | Badge labels/classes use `OrderStatus` constants |
