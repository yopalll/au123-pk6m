# README — Bug Audit (2 May 2026)

> **Scope:** End-to-end read of the recently shipped features (Owner Panel, Review System, Smart Booking, Midtrans Payment). Each finding includes severity, evidence, and a concrete fix.
> **Branch when audited:** `feature/smart-booking-payment` @ commit `89493fd`
> **Status:** Findings catalogued. Fixes NOT YET applied — see "Recommended fix order" at the bottom.
> **Companion docs:** [README-OWNER-PANEL.md](README-OWNER-PANEL.md), [README-REVIEW-SYSTEM.md](README-REVIEW-SYSTEM.md), [README-SMART-BOOKING-PAYMENT.md](README-SMART-BOOKING-PAYMENT.md)

---

## Severity legend

- 🔴 **Critical** — breaks a feature on the happy path; fix before demo.
- 🟠 **High** — visible behaviour gap; data or UX is wrong but app still runs.
- 🟡 **Medium** — corner case, inconsistency, or technical debt.
- 🟢 **Low** — cosmetic / pre-existing / documentation.

---

## 🔴 BUG-01 — `order.status` enum has no `confirmed` value

**The Midtrans payment flow cannot complete.** The webhook tries to flip `order.status = 'confirmed'` after a successful settlement, but the schema enum was created before the payment work and only allows `pending | success | canceled`. MySQL rejects the write.

**Evidence:**

```sql
mysql> SHOW COLUMNS FROM `order` WHERE Field='status';
Type: enum('pending','success','canceled')
```

Reproducer:

```php
DB::table('order')->insert([... 'status' => 'confirmed']);
// SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1
```

**Where it bites:**
- [app/Http/Controllers/PaymentController.php:147,154](app/Http/Controllers/PaymentController.php#L147) — `$order->status = 'confirmed'` in the webhook handler.
- [app/Filament/Owner/Resources/OrderResource.php:74,75](app/Filament/Owner/Resources/OrderResource.php#L74) — owner panel "Confirm" action button.
- [app/Filament/Resources/OrderResource.php:25-30](app/Filament/Resources/OrderResource.php#L25) — admin OrderResource form has `'confirmed' => 'Confirmed'` option.

**Fix:** Add a migration that extends the enum.

```php
// database/migrations/2026_05_02_110000_extend_order_status_enum.php
public function up(): void
{
    DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending','confirmed','success','canceled') NOT NULL DEFAULT 'pending'");
}
public function down(): void
{
    DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending','success','canceled') NOT NULL");
}
```

---

## 🔴 BUG-02 — Vite manifest missing on first run

When a fresh checkout serves the public layout without `npm run build` (or `npm run dev` running), `public.blade.php`'s `@vite(...)` throws `ViteManifestNotFoundException`. Any visit to `/` (or anywhere using the public layout) returns a 500.

**Evidence:** [storage/logs/laravel.log:537](storage/logs/laravel.log#L537):

```
ViteManifestNotFoundException: Vite manifest not found at:
C:\treatwell2\VIYGO\public\build/manifest.json
```

**Fix:** Two options, pick one:
1. **Recommended — build the assets:** `npm install && npm run build` (one-time after fresh clone). For dev with HMR, `npm run dev`.
2. Document in `README.md` that `public/build/` must exist before serving — already implied but easy to miss.

---

## 🟠 BUG-03 — `confirmed` orders disappear from `/akun/bookings`

The customer's "My Bookings" tab map only knows three statuses:

```php
// app/Http/Controllers/AkunController.php:27-31
$statusMap = [
    'mendatang'  => 'pending',
    'selesai'    => 'success',
    'dibatalkan' => 'canceled',
];
```

Once BUG-01 is fixed and an order moves to `confirmed`, it falls out of the Upcoming tab (which only shows `pending`). The customer can't see their own paid booking.

**Where:** [app/Http/Controllers/AkunController.php:27](app/Http/Controllers/AkunController.php#L27)

**Fix:** Match an array of statuses for "Upcoming":

```php
$statusMap = [
    'mendatang'  => ['pending', 'confirmed'],
    'selesai'    => ['success'],
    'dibatalkan' => ['canceled'],
];

$orders = Order::where('id_user', auth()->id())
    ->when(isset($statusMap[$tab]), fn ($q) => $q->whereIn('status', (array) $statusMap[$tab]))
    ...
```

Also update the badge label map in [resources/views/akun/bookings.blade.php:21](resources/views/akun/bookings.blade.php#L21) to add `'confirmed' => 'Upcoming'` (or `'Paid'`).

---

## 🟠 BUG-04 — Owner monthly revenue widget hides paid bookings

```php
// app/Filament/Owner/Widgets/OwnerStatsOverview.php:35-39
$thisMonthRevenue = Order::whereIn('id_salon', $salonIds)
    ->where('status', 'success')           // ← only completed appointments
    ->whereYear('date_order', now()->year)
    ->whereMonth('date_order', now()->month)
    ->sum('total_pembayaran');
```

After Midtrans settles a booking, it sits at `confirmed` until the owner marks it `success` (after the appointment). The widget therefore reports £0 in revenue all month even though the cash has cleared.

**Where:** [app/Filament/Owner/Widgets/OwnerStatsOverview.php:36](app/Filament/Owner/Widgets/OwnerStatsOverview.php#L36)

**Fix:** Two semantics worth distinguishing — money received vs money earned:

```php
// Cash collected (paid bookings, regardless of completion)
$thisMonthRevenue = Pembayaran::whereHas('order', fn ($q) => $q->whereIn('id_salon', $salonIds))
    ->where('status_pembayaran', 'completed')
    ->whereYear('tanggal_bayar', now()->year)
    ->whereMonth('tanggal_bayar', now()->month)
    ->sum('jumlah_bayar');
```

Or, simpler: `whereIn('status', ['confirmed', 'success'])` on Order if you'd rather keep one query.

---

## 🟠 BUG-05 — Cancel-spelling inconsistency: `canceled` vs `cancelled`

Two different status enums use two different spellings:

| Table | Column | Cancellation value |
|-------|--------|--------------------|
| `order` | `status` | **`canceled`** (American, single L) |
| `order_detail` | `status` | **`cancelled`** (British, double L) |

Code in `BookingSlotService::busyByStaff` excludes `whereNotIn('status', ['canceled'])` on the Order — correct for that table. But anyone querying `OrderDetail` needs `'cancelled'` (with double L) to match. If a future reviewer copy-pastes the spelling, they'll get the wrong filter.

**Where:**
- [database/migrations/2026_04_12_000008_create_order_table.php:32](database/migrations/2026_04_12_000008_create_order_table.php#L32)
- [database/migrations/2026_04_12_000014_create_order_detail_table.php:29](database/migrations/2026_04_12_000014_create_order_detail_table.php#L29)

**Fix:** Pick one spelling and migrate the other. Industry convention in code is `canceled` (single L, matches Carbon, Stripe, Laravel). Recommendation: standardise on `canceled` and add a migration that:

```php
DB::statement("UPDATE order_detail SET status = 'canceled' WHERE status = 'cancelled'");
DB::statement("ALTER TABLE order_detail MODIFY status ENUM('pending','in_progress','completed','canceled') NOT NULL DEFAULT 'pending'");
```

If you'd rather not migrate, at least add a `App\Constants\OrderStatus` class so the strings live in exactly one place.

---

## 🟡 BUG-06 — Owner staff schedule form writes lowercase day, schema canonical is capitalized

The `staff_schedule.hari` enum is `Monday | Tuesday | ...` (capitalized), but the Filament form Owner uses to create schedules sends lowercase keys:

```php
// app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php:22-30
->options([
    'monday'    => 'Monday',
    'tuesday'   => 'Tuesday',
    ...
])
```

MySQL accepts the insert because string comparisons are case-insensitive by default (the enum stores whichever case was given). `BookingSlotService::availableSlots` happens to query with `ucfirst(strtolower(...))` so the lookup also matches — but **only by accident** of MySQL's collation. If the database ever migrates to a binary collation, or the schema is moved to PostgreSQL, both sides break.

**Where:** [app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php:22](app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php#L22)

**Fix:** Use the canonical capitalized form:

```php
->options([
    'Monday'    => 'Monday',
    'Tuesday'   => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday'  => 'Thursday',
    'Friday'    => 'Friday',
    'Saturday'  => 'Saturday',
    'Sunday'    => 'Sunday',
])
```

---

## 🟡 BUG-07 — `BookingController::batal` only cancels `pending` orders

After Midtrans settlement the order is `confirmed`; the customer can no longer cancel from `/akun/bookings`. Whether this is "by design" depends on the refund policy — but right now there is no UI feedback explaining why the Cancel button vanished, just no button.

**Where:** [app/Http/Controllers/BookingController.php:155](app/Http/Controllers/BookingController.php#L155):

```php
->where('status', 'pending')
->firstOrFail();
```

**Fix:**
- **If cancellation should be allowed for paid bookings:** widen to `whereIn('status', ['pending', 'confirmed'])`. Then add a Midtrans refund call: `Midtrans\Transaction::refund($payment->id_transaksi, [...])` and update `pembayaran.status_pembayaran = 'refunded'` (requires extending the enum: `pending|completed|failed|refunded`).
- **If cancellation should NOT be allowed:** show a "Contact support to cancel a paid booking" message in the view instead of silently hiding the button. See [resources/views/akun/bookings.blade.php:53](resources/views/akun/bookings.blade.php#L53).

---

## 🟡 BUG-08 — Owner panel calendar selection survives month navigation

In `booking/create.blade.php`, the Step-2 calendar's `selectedDay` is a bare integer. If a user picks day 15 in February then clicks `›` to March, day 15 in March still appears highlighted because the comparison is `selectedDay === cell.day`.

**Pre-existing:** This was inherited from before the smart-booking rewrite — not introduced by recent work. Worth a fix anyway because it's confusing to users.

**Where:** [resources/views/booking/create.blade.php:90](resources/views/booking/create.blade.php#L90)

**Fix:** Track `selectedYearMonth` alongside `selectedDay`, or store the date as `YYYY-MM-DD` and compare on that.

---

## 🟡 BUG-09 — `staff_schedule` empty-roster fallback is dead code

In `BookingSlotService::availableSlots` lines 86-88:

```php
if (empty($availableStaff) && $staffList->every(fn ($s) => $s->schedules->isEmpty())) {
    $availableStaff = [['id' => 0, 'name' => 'Any staff']];
}
```

This branch was meant to handle "salon has staff but no schedules" by falling back to "Any staff". But the line above (`staffWorking()`) already returns `true` when `$schedules->isEmpty()`, so all staff are pushed into `$availableStaff` — meaning `empty($availableStaff)` is never true on the path this branch was meant to catch. The fallback only fires when the salon has **zero** staff records at all, in which case we should probably return no slots and tell the customer "salon hasn't onboarded staff yet" instead of silently booking.

**Where:** [app/Services/BookingSlotService.php:86-88](app/Services/BookingSlotService.php#L86)

**Fix:** Either remove the dead-looking branch and add an explicit "no staff" handling, or move the schedule-emptiness check to the top so it's deterministic:

```php
if ($staffList->isEmpty()) {
    return collect(); // salon has no staff records
}
```

Note: live data has `staff` rows for most salons but `staff_schedule` is empty (per `progress.md` line 281), so the current code path *works in practice* — every staff matches every slot during salon hours. This is more about code clarity than runtime breakage.

---

## 🟡 BUG-10 — Slot service ignores `staff_service` pivot

`Service` and `Staff` have a many-to-many pivot `staff_service` — meaning "which staff can deliver which service". The slot generator currently treats every active staff at the salon as bookable for every active service. If a salon configures specific staff per service, the system happily books a "haircut specialist" for a manicure.

**Where:** [app/Services/BookingSlotService.php:54-66](app/Services/BookingSlotService.php#L54). Also documented as a known gap in [README-SMART-BOOKING-PAYMENT.md](README-SMART-BOOKING-PAYMENT.md) ("staff_service pivot is ignored"). Promoting this to BUG-status because it's a correctness issue, not just a polish.

**Fix:** Add a constraint in the staff query (with a feature flag so seeded salons without pivot rows still work):

```php
$staffQuery = Staff::query()
    ->where('id_salon', $salon->id_salon)
    ->where('status', 'active');

// Only filter when at least one pivot row exists for this service.
if (DB::table('staff_service')->where('id_service', $service->id_service)->exists()) {
    $staffQuery->whereHas('services', fn ($q) => $q->where('service.id_service', $service->id_service));
}
```

---

## 🟢 BUG-11 — Filament admin `OrderResource` references `confirmed` status

The admin's Order form has a `confirmed` option (line 27), but until BUG-01 is fixed, choosing it will silently fail-truncate to `''`. Cosmetic for now because admins probably don't manually flip status — but flagged for completeness.

**Where:** [app/Filament/Resources/OrderResource.php:27](app/Filament/Resources/OrderResource.php#L27)

**Fix:** Auto-resolved when BUG-01 lands.

---

## 🟢 BUG-12 — `public/build/manifest.json` not committed (and shouldn't be)

The Vite-generated manifest lives in `public/build/`. It's `.gitignore`d (correctly), so anyone cloning sees [BUG-02]. There's no documentation in `README.md` warning new contributors. Fix is a one-paragraph note in `README.md` § "Local setup" reminding them to run `npm run build` after `composer install`.

---

## Things that are NOT bugs

Worth recording so future reviewers don't waste time:

- **`php artisan about` showed `Filament\PanelProvider not found`** in earlier sessions — that was a missing `vendor/filament` directory before the user ran `composer install`. Resolved on this branch.
- **`php -l` reports zero syntax errors** across `app/**/*.php`.
- **`php artisan view:cache` succeeds** — every Blade view in `resources/views/**` compiles.
- **`BookingSlotService::availableSlots` returns 16 slots** for the seeded `Novoblanc London` salon with a 90-min service over a 09:00-18:00 window — math is correct.
- **`ReviewObserver` fires on Review `saved`/`deleted`** and updates `salon.rating` + `total_review` via `saveQuietly()` — manually verified.
- **All four new routes (`booking.slots`, `booking.payment`, `booking.payment.token`, `midtrans.webhook`) appear in `php artisan route:list`** with the correct middleware.

---

## Recommended fix order

If you only have time for one round of fixes before the next demo:

1. **BUG-01** (5 min) — write the enum migration. **Without this the payment webhook fails silently.**
2. **BUG-03** (10 min) — fix the bookings tab status map. **Without this customers can't see their own paid bookings.**
3. **BUG-04** (5 min) — fix the owner revenue widget. **Without this owners think nobody's paying.**
4. **BUG-02** (1 min) — `npm run build` once.
5. **BUG-06** (2 min) — fix the lowercase day option in the schedules form.
6. **BUG-05** (15 min) — pick a cancel-spelling and migrate.

The rest can wait for a polish pass.

## Want me to apply the fixes?

Say the word and I'll do the top-3 critical ones (BUG-01, BUG-03, BUG-04) on a `fix/post-payment-audit` branch with one commit per finding.

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
