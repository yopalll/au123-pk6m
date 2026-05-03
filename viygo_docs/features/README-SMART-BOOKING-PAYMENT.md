# README — Smart Booking + Midtrans Snap Payment (TUGAS 4 / PRIORITAS 4 + 5)

> **Feature:** Server-driven booking-slot generator + external payment via Midtrans Snap (Sandbox).
> **Status:** ✅ Implemented — 2 May 2026.
> **Branch:** `feature/smart-booking-payment` (cut from `feature/review-system`).
> **Companion docs:** [README-OWNER-PANEL.md](README-OWNER-PANEL.md), [README-REVIEW-SYSTEM.md](README-REVIEW-SYSTEM.md), [README-CUSTOMER-PANEL.md](README-CUSTOMER-PANEL.md)

---

## Two halves of one flow

```
[Customer]
  Pick Service ─▶ Pick Date+Staff ─▶ /booking/slots (JSON, server-computed)
                                       │
                              Pick Time ▼
                          POST /salon/{slug}/booking
                                       │  (re-verifies availability,
                                       │   picks staff if "Any staff",
                                       │   creates Order + OrderDetail)
                                       ▼
                              /booking/{kode}/payment
                                       │  POST /payment/token  →  Snap.getSnapToken
                                       ▼
                              window.snap.pay(token, callbacks)
                                       │
                              Midtrans hosted pop-up
                                       │
              ┌────────────────────────┼────────────────────────┐
              ▼                        ▼                        ▼
        onSuccess             /midtrans/webhook            onError/onClose
          → /konfirmasi    (signature-verified, S2S)     stay on /payment
                          updates pembayaran +
                          order.status: pending → confirmed
```

The smart-booking half (PRIORITAS 5) and the payment half (PRIORITAS 4) share the same wizard view, so they are shipped on one branch.

## Files

### Created
| File | Purpose |
|------|---------|
| `app/Services/BookingSlotService.php` | Pure-PHP slot generator. Walks salon `opening_time → closing_time` in 30-min steps, intersects with `staff_schedule` for the chosen weekday, subtracts overlapping non-canceled `OrderDetail` rows. Returns a collection of `{time:'HH:MM', staff:[{id,name},...]}`. |
| `app/Http/Controllers/PaymentController.php` | `show()` (host page), `createSnapToken()` (idempotent), `webhook()` (S2S notification handler with SHA512 signature check). Constructor sets up `Midtrans\Config` from `config/services.midtrans.*`. |
| `resources/views/booking/payment.blade.php` | Snap pop-up host. Loads either `app.sandbox.midtrans.com/snap/snap.js` or `app.midtrans.com/snap/snap.js` depending on `MIDTRANS_PRODUCTION`. POSTs to `/payment/token`, then calls `window.snap.pay(token, callbacks)`. Falls back to a "not configured" banner if `MIDTRANS_CLIENT_KEY` is empty. |
| `database/migrations/2026_05_02_100000_add_midtrans_columns_to_pembayaran_table.php` | Adds `id_transaksi`, `snap_token`, `raw_response` (JSON) to `pembayaran`. |
| `README-SMART-BOOKING-PAYMENT.md` | This file. |

### Modified
| File | Change |
|------|--------|
| `app/Http/Controllers/BookingController.php` | Constructor injects `BookingSlotService`. `create()` also passes `$staff` for the dropdown. New `getSlots()` returns JSON. `store()` validates an extra `id_staff`, **re-verifies the slot is still available** (anti-race), auto-picks a staff member if user said "Any staff", and now redirects to `/booking/{kode}/payment` instead of `/konfirmasi`. |
| `resources/views/booking/create.blade.php` | Step 2 replaced: static 14-slot grid → staff dropdown + dynamic slot grid fetched from `/booking/slots`. Step 3 now shows the chosen staff and renames the submit button to "Continue to Payment". `id_staff` hidden field added. |
| `app/Models/Pembayaran.php` | Three new fillable columns: `id_transaksi`, `snap_token`, `raw_response`. `raw_response` cast to `array`. |
| `routes/web.php` | New routes: `GET /salon/{slug}/booking/slots` (`booking.slots`), `GET /booking/{kode}/payment` (`booking.payment`), `POST /booking/{kode}/payment/token` (`booking.payment.token`), `POST /midtrans/webhook` (`midtrans.webhook`). Imported `PaymentController`. |
| `bootstrap/app.php` | `validateCsrfTokens(except: ['midtrans/webhook'])` so Midtrans's S2S POST doesn't fail CSRF. |
| `config/services.php` | New `midtrans` block (server_key, client_key, is_production, is_sanitized, is_3ds). |
| `.env.example` | Added `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_PRODUCTION`, `VITE_MIDTRANS_CLIENT_KEY`, plus `VIYGO_SUPPORT_EMAIL` / `VIYGO_HELP_EMAIL` documentation. |
| `composer.json` / `composer.lock` | Added `midtrans/midtrans-php ^2.6`. |
| `progress.md` | PRIORITAS 4 + 5 → ✅. Total bumped to ~93%. Booking slot static issue stricken-through. |

## How smart booking works

The brief: replace the static 14-slot grid with availability that respects salon hours, staff schedules, and prevents double-booking. We do this purely on the server — `BookingSlotService::availableSlots(salon, service, date, ?staffId)` is the one source of truth, called by both `getSlots()` (browser-facing JSON) and `isSlotAvailable()` / `pickStaffForSlot()` (server-side guards in `store()`).

Walk-through:

1. **Outer window** — `salon.opening_time` → `salon.closing_time`. Step in 30-minute increments. Stop at `closing_time - service.durasi` so a 60-min service can't start at 19:30 of a 20:00-close salon.
2. **Per-staff filter** — for each staff at this salon (or just the one the user picked), check `staff_schedule` rows where `hari = ucfirst(weekday)` and `is_available = true`. The proposed `[start, end]` window must fit entirely inside one of those schedule windows.
3. **Subtract busy intervals** — pull `OrderDetail` rows for those staff on the target date, exclude `canceled` orders, and reject any candidate slot that overlaps an existing booking using the standard "two intervals overlap iff NOT (a.end ≤ b.start OR a.start ≥ b.end)" check.
4. **Past-slots filter** — if the chosen date is today, drop slots whose `time` is in the past.
5. **Empty-roster fallback** — if the salon literally has zero `staff_schedule` rows, the service falls back to "follows salon hours" so seeded data without staff schedules still books. The slot returns `[{id:0, name:'Any staff'}]` in that case; `BookingController::store` auto-resolves to `id_staff = null`.

Anti-race: `store()` calls `isSlotAvailable()` again right before insert. Two customers racing for the same slot — the loser gets a flash error ("Sorry, that slot was just taken") instead of a successful double-booking.

## How Midtrans Snap works

Pieces:

- `MidtransConfig::$serverKey` is set from `config('services.midtrans.server_key')` in the controller's constructor — that's how the SDK authenticates to Midtrans's REST endpoints.
- `Snap::getSnapToken($params)` requires `transaction_details.order_id` (we pass `kode_order`, e.g. `VYG-AB12CD34`), `gross_amount` (rounded GBP), and `item_details[]` reconciled to match the gross.
- The browser loads `https://app.sandbox.midtrans.com/snap/snap.js` with `data-client-key="..."`, then calls `window.snap.pay(token, callbacks)` to open the pop-up.

The webhook is the source of truth — onSuccess in the browser is just for UX. We never trust browser state for status changes:

- `Midtrans\Notification` re-fetches the transaction from Midtrans using server_key, so a spoofed POST can't lie.
- We *also* check the SHA512 signature ourselves (`hash('sha512', orderId + statusCode + grossAmount + serverKey)`) so spoofed posts can't even reach the DB writes.
- Status mapping follows the [official table](https://docs.midtrans.com/docs/https-notification-webhooks): `capture` (with non-challenge fraud) → completed + order confirmed; `settlement` → same; `pending` → pending; `deny`/`expire`/`cancel`/`failure` → failed (order stays pending so user can retry).

## Configuration / runbook

`.env` setup (sandbox):

```
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_PRODUCTION=false
```

Get sandbox keys at <https://dashboard.sandbox.midtrans.com/settings/config_info>.

Webhook URL — Midtrans dashboard › Settings › Configuration › "Payment Notification URL":

```
https://your-app.test/midtrans/webhook
```

Local development needs a public tunnel — `ngrok http 8000` works, point Midtrans at the ngrok URL. The signature check uses your `server_key` so a tunnelled local app verifies the same way production would.

## Status — what works

✅ Customer picks service → date → staff → time → confirms.
✅ Slot grid is server-computed, accounts for salon hours, `staff_schedule`, and existing bookings.
✅ Race condition: a second customer booking the same slot gets a friendly error instead of a duplicate.
✅ "Any staff" auto-resolves to a concrete `id_staff` at insert time (or `null` when the roster has no schedules).
✅ After confirm, user lands on the Snap pop-up host page.
✅ With sandbox keys: clicking Pay opens the Snap pop-up; on success the user is redirected to `/konfirmasi`.
✅ `/midtrans/webhook` updates `pembayaran` (status, `id_transaksi`, `raw_response`) and flips `order.status` to `confirmed`.
✅ Without keys: the payment page shows a friendly "gateway not configured" banner; everything else still works.
✅ `php artisan migrate` runs cleanly; route list shows all new routes.

## Status — known gaps

⚠️ **Snap.pay credit-card flow needs `data-environment="sandbox"` for some browsers**. We rely on the snap.js URL choice (`sandbox.` vs production) which is the documented approach. If you see "client key invalid" in console, double-check the URL matches the key environment.

⚠️ **No order-expiry job.** A `pending` order whose Snap token expired stays `pending` forever. → Add a `PruneExpiredOrders` console command + scheduled task that flips `pending` orders >24h old to `canceled` (and the staff slot frees up automatically because the slot service excludes `canceled`).

⚠️ **No retry queue on the webhook.** If the webhook fires while the app is down, Midtrans retries 3 times then gives up. → Add a `php artisan midtrans:reconcile {kode}` command that calls `Midtrans\Transaction::status($kode)` to backfill missed notifications.

⚠️ **`staff_service` pivot is ignored.** Slot service treats every active staff as bookable for every active service. The dataset doesn't seed `staff_service` rows in a meaningful way; if/when that changes, add `whereHas('services', fn ($q) => $q->where('id_service', $service->id_service))` to the staff query.

⚠️ **No cancellation refund.** `BookingController::batal` flips status to `canceled` but doesn't trigger a Midtrans refund. → Use `Midtrans\Transaction::refund($txId, $params)` and update `pembayaran.status_pembayaran = 'refunded'` (add this enum value).

⚠️ **GBP currency.** Midtrans Sandbox accepts GBP for testing but production-IDR only. If the salon population shifts to Indonesia, convert prices before passing to Snap.

## Verification

After `composer install` and `php artisan migrate`:

1. `php artisan route:list | grep -E 'booking|payment|midtrans'` → should list 4 new routes.
2. As a `customer`, go to `/salon/{slug}/booking`. Pick a service. Pick a date and a staff (or "Any staff"). The slot grid should populate.
3. Submitting confirms goes to `/booking/{kode}/payment`.
4. Without `MIDTRANS_*` keys → page renders, button disabled, banner shown. **PASS**.
5. With sandbox keys → click Pay → Snap pop-up opens. Use a [Midtrans test card](https://docs.midtrans.com/docs/testing-payment-on-sandbox), e.g. `4811 1111 1111 1114`, OTP `112233`. Pay-ment success → redirect to `/konfirmasi`.
6. Tail `storage/logs/laravel.log` while the webhook fires; should see no "signature mismatch" warnings.
7. After webhook completes: `Order::find(...)->status === 'confirmed'`, `Pembayaran::where('id_order', ...)->status_pembayaran === 'completed'`, `id_transaksi` populated.

## Files summary

### Created
```
app/Services/BookingSlotService.php
app/Http/Controllers/PaymentController.php
resources/views/booking/payment.blade.php
database/migrations/2026_05_02_100000_add_midtrans_columns_to_pembayaran_table.php
README-SMART-BOOKING-PAYMENT.md  ← this file
```

### Modified
```
app/Http/Controllers/BookingController.php
app/Models/Pembayaran.php
resources/views/booking/create.blade.php
routes/web.php
bootstrap/app.php
config/services.php
.env.example
composer.json
composer.lock
progress.md
```

---

## Next Action — for the next AI agent

In priority order (per `prompt-next.md`):

### 1. **TUGAS 7 — Header dummy pages polish** (medium, ~2-3h)

`/gift-card`, `/lookbook`, `/treatment-files`, `/mitra` exist but are sparse. Use [README-GAMBAR-STATIS.md](README-GAMBAR-STATIS.md) as the visual asset manifest. Each page should have a hero, a couple of content sections, a CTA — same vibe as the static pages already shipped.

### 2. **Smart-booking polish leftovers**

- `php artisan db:seed --class=StaffScheduleSeeder` — generate Mon–Fri 09:00–18:00 schedules for every active staff so the booking grid doesn't fall back to "follows salon hours".
- Cancel → Midtrans refund (`Midtrans\Transaction::refund`) + `pembayaran.status = 'refunded'` (add enum value).
- `php artisan midtrans:reconcile {kode}` console command that calls `Transaction::status($kode)` for orders whose webhook never fired.
- Order TTL: scheduled task to flip `pending` orders >24h old to `canceled` (and free their slot).
- Filter slot service to honour `staff_service` once that pivot has real data.

### 3. **TUGAS 9 (Optional / Batch 2) — Skincare + Community**

Architecture only; placeholder backlog. Don't start until 1–8 are 100%.

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
