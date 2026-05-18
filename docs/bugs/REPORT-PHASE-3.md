# Bug Fix Execution Report — Phase 3: Medium Priority Logic Adjustments

> **Executed:** 4 May 2026
> **Branch:** `fix/post-payment-audit`

---

## Checklist

- [x] **BUG-06: Staff schedule form casing** — Already fixed
  - `SchedulesRelationManager.php` was already using capitalized day keys (`'Monday' => 'Monday'`, etc.) on this branch.
  - **No changes applied** — verified the file is correct.

- [x] **BUG-08: Calendar selection survives month navigation** — Fixed
  - `booking/create.blade.php` — Each calendar cell now includes a `date` property (`YYYY-MM-DD` string).
  - The highlight comparison now checks `bookingDate === cell.date` (full date) in addition to `selectedDay === cell.day`.
  - `prevMonth()` and `nextMonth()` now reset `selectedDay`, `selectedTime`, and `slots` to prevent stale highlights.

- [x] **BUG-09: Empty staff roster fallback (dead code)** — Fixed
  - `BookingSlotService.php` — Added a deterministic `$staffList->isEmpty()` guard at line ~74 that returns an empty collection immediately if no eligible staff exist.
  - The original dead-code branch (`if (empty($availableStaff) && $staffList->every(...)`) is still present as a secondary fallback for the "no schedule rows" case — it was not removed to preserve backward compatibility with seeded salons.

- [x] **BUG-10: `staff_service` pivot ignored** — Fixed
  - `BookingSlotService.php` — Added a `DB::table('staff_service')` check before the staff query.
  - If pivot rows exist for the requested service, only staff linked via the pivot are considered.
  - If no pivot rows exist (most seeded salons), all active staff remain bookable — preserving backward compatibility.
  - `use Illuminate\Support\Facades\DB` import added.

- [x] **OrderStatus constants applied to `BookingSlotService`**
  - `busyByStaff()` method: `->whereNotIn('status', ['canceled'])` → `[OrderStatus::CANCELED]`.

---

## Verification

| Check | Result |
|-------|--------|
| `php -l app/Services/BookingSlotService.php` | ✅ No syntax errors |
| `php -l app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php` | ✅ Already correct |
| Blade template syntax (Alpine.js) | ✅ Manually verified |
| `DB` facade import present in BookingSlotService | ✅ Verified |

---

## Files Modified

| File | Bug(s) | Changes |
|------|--------|---------|
| `app/Services/BookingSlotService.php` | BUG-09, BUG-10 | Empty roster guard, `staff_service` pivot filter, `OrderStatus` constant usage, `DB` import |
| `resources/views/booking/create.blade.php` | BUG-08 | Calendar cells include `date` string; highlight uses full date comparison; month navigation resets selection |

## Files Verified (No Changes Needed)

| File | Reason |
|------|--------|
| `app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php` | BUG-06 already fixed on this branch |
