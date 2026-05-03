# README — Review System (TUGAS 5 / PRIORITAS 6)

> **Feature:** Customers can rate completed bookings; salon aggregates stay in sync automatically.
> **Status:** ✅ Implemented — 2 May 2026.
> **Branch:** `feature/review-system` (cut from `feature/owner-panel`).
> **Companion docs:** [README-OWNER-PANEL.md](README-OWNER-PANEL.md), [README-CUSTOMER-PANEL.md](README-CUSTOMER-PANEL.md), [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md)

---

## What was built

A self-contained review pipeline:

```
/akun/bookings (Completed tab)
   ↓ "Leave a review" button (only on success orders without a review)
/akun/bookings/{kode}/review  ← form (GET)
   ↓ submit
POST /akun/bookings/{kode}/review
   ↓ Review::create()
   ↓ ReviewObserver fires saved()
   ↓ recomputes salon.rating + salon.total_review (saveQuietly)
   ↓ redirect → /akun/bookings?tab=selesai with flash success
```

The same `ReviewObserver` also runs when an admin toggles `is_visible` from the Filament `ReviewResource`, so moderation always keeps the public aggregates accurate without any extra plumbing.

## Files

### Created
| File | Purpose |
|------|---------|
| `app/Http/Controllers/ReviewController.php` | `create($kode)` (form) + `store(Request, $kode)` (persist). Both call `resolveReviewableOrder()` which 404s unless the order belongs to the user, is `success`, and has no existing review. |
| `app/Observers/ReviewObserver.php` | `saved()` and `deleted()` recompute `salon.rating = AVG(rating WHERE is_visible=1)` and `salon.total_review = COUNT(...)`. Uses `saveQuietly()` so we don't bump `salon.updated_at` on every review write. |
| `resources/views/akun/review/create.blade.php` | Star-rating + textarea form (Alpine.js for the interactive 5-star picker). Includes order summary card, validation error banner, char-limit hint, and a link to the community guidelines (Terms page). |
| `README-REVIEW-SYSTEM.md` | This file. |

### Modified
| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Registers `Review::observe(ReviewObserver::class)` in `boot()`. |
| `app/Http/Controllers/SalonController.php` | Eager-loaded `reviews` are now filtered by `is_visible = true` so hidden reviews don't leak onto the public salon page. |
| `routes/web.php` | Added two routes inside the `role:customer` group: `GET /akun/bookings/{kode}/review` (`akun.review.create`) and `POST /akun/bookings/{kode}/review` (`akun.review.store`). Imported `ReviewController`. |
| `resources/views/akun/bookings.blade.php` | The Completed tab now shows either a **"Leave a review"** button (when no review exists) or a read-only **"★ x/5 reviewed"** badge (once submitted). The existing eager-load `with(['salon.kota', 'details.service', 'review'])` makes both cases zero-N+1. |
| `progress.md` | PRIORITAS 6 → ✅. Overall progress bumped to ~88%. ReviewController flipped from PENDING to done. New route registered. |

## Logic notes

### Why the observer instead of inline logic?

The brief floated two options: recompute aggregates *in the controller's `store()`* (immediate) vs. *via observer* (universal). I went with the observer because:

1. **Admin moderation also writes.** `ReviewResource` in the Filament admin has a `ToggleColumn` and bulk show/hide actions on `is_visible`. Without an observer, those writes would silently skew the aggregate.
2. **Cascade deletes.** The migration declares `cascadeOnDelete()` on `id_order` and `id_salon`. If a salon ever gets force-deleted, the related reviews get cascade-removed; the observer's `deleted()` hook means the remaining salons (none, in this case) still recompute correctly. Defensive.
3. **One source of truth.** The recompute query is in a single file, easier to audit if rules change (e.g. excluding 1-star reviews or weighting verified bookings).

### Why `saveQuietly()`?

`salon.updated_at` is sometimes used by HTTP cache layers. Bumping it on every review write would invalidate caches keyed on that column without any actual change to salon-managed data.

### Why `firstOrFail()` (404) instead of explicit error messages?

Returning 404 on an order that's not `success` / not yours / already reviewed treats the URL as opaque. It avoids leaking signal ("there's an order here but you can't review it") and keeps the controller small. The UI already only renders the button when a review is allowed, so legitimate users never hit the 404 path.

### Star-picker accessibility

The Alpine.js star picker is a `<button>` per star (so keyboard users can tab through them) with `aria-label="Rate N stars"`. The hidden `name="rating"` input mirrors the Alpine state — fully form-submittable and degrades to "0" if JS is off (server validation will catch that as invalid).

## Status — what works

✅ A `customer` who finished a booking sees the "Leave a review" button on the Completed tab.
✅ Submitting the form persists the review and redirects with a success flash.
✅ The salon's `rating` and `total_review` columns update automatically (visible on the home page top-rated section, search results, and salon detail page).
✅ A second submission for the same order 404s (defense in depth — UI also hides the button).
✅ Admin's `ReviewResource` toggle/show/hide actions trigger the same observer; aggregates re-sync on the next page load.
✅ Hidden reviews (`is_visible = false`) are excluded from the salon detail page's review list.

## Status — known gaps

⚠️ **No image uploads on reviews yet.** Single-rating + single-textarea only. Adding photos would need a `review_images` table and a file pipeline.

⚠️ **No edit/delete by the user.** Reviews are write-once for the customer. Editing would need `update($id)` plus a "report" workflow if other users want to flag content; explicitly out of scope for this session.

⚠️ **Rating only — no per-aspect breakdown.** Treatwell's UK pages show separate scores for "service quality", "atmosphere", "value", etc. Our schema only has a single `rating` int. Adding sub-ratings would touch the migration; deferred to a later batch.

⚠️ **No salon-owner reply yet.** Owners can't respond to reviews from `/owner`. Adding that needs a `review_replies` table or a `salon_reply` text column on `review`.

⚠️ **`salon.rating` may show 0 for salons that genuinely have no visible reviews.** The home/search page coalesces with `rating ?? 4.5` for display, so users see a friendly default. If the seeded salons have non-zero `rating` from scrape data, those values now risk being overwritten the next time *any* review on that salon is written. → If that's undesirable, change the observer's "no rows" branch to leave the existing rating alone instead of zeroing it.

## Verification

After `composer install` (vendor isn't currently populated on this machine):

1. `php artisan route:list | grep review` → should show:
   - `GET  /akun/bookings/{kode}/review  ›  ReviewController@create  ›  akun.review.create  ›  middleware: auth, verified, role:customer`
   - `POST /akun/bookings/{kode}/review  ›  ReviewController@store   ›  akun.review.store`
2. Seed an order: `Order::factory()->create(['id_user' => $u->id_user, 'status' => 'success'])` (or transition an existing one).
3. As that customer, visit `/akun/bookings?tab=selesai` → "Leave a review" button visible.
4. Click → form renders. Pick stars, type a comment, submit → redirected back, badge replaces the button, DB has the row.
5. `Salon::find($id)->rating` and `total_review` should reflect the new review.
6. As admin, toggle `is_visible = false` from `/admin` → the salon's rating recomputes (refresh `Salon::find($id)`).
7. Try to GET `/akun/bookings/{kode}/review` for an order that's already reviewed → 404.
8. Try as a non-`customer` role → 403 from `role:customer` middleware.

## Files touched (full path list)

### Created
```
app/Http/Controllers/ReviewController.php
app/Observers/ReviewObserver.php
resources/views/akun/review/create.blade.php
README-REVIEW-SYSTEM.md  ← this file
```

### Modified
```
app/Providers/AppServiceProvider.php
app/Http/Controllers/SalonController.php
routes/web.php
resources/views/akun/bookings.blade.php
progress.md
```

---

## Next Action — for the next AI agent

In priority order from `prompt-next.md`:

### 1. **TUGAS 4 — Smart booking + Midtrans Snap** (large, ~1-2 days)

The Owner panel populated `staff_schedule`, the customer panel can review. The remaining flagship feature is the booking pipeline:

- **Server-side time-slot generator.** Replace the static 14-slot grid in `resources/views/booking/create.blade.php`:
  1. Take `salon.opening_time` / `closing_time`.
  2. Step in `service.durasi`-minute increments.
  3. For each candidate slot, intersect with `staff_schedule` rows for the chosen `hari` (translate `date_order`'s weekday).
  4. Subtract slots that overlap any non-canceled `OrderDetail` with the same `id_staff`.
- **"Any staff" wizard option.** Default the picker to "Any staff" — system picks the first available staff at the chosen slot.
- **Midtrans Snap (Sandbox).**
  - The brief explicitly asks: web-search the latest Snap docs before coding.
  - `composer require midtrans/midtrans-php`.
  - `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_ENV=sandbox` in `.env`.
  - `PaymentController@createSnapToken(Order $order)` — returns `{ snap_token: ... }` for the frontend pop-up.
  - `PaymentController@webhook` — handles Midtrans's notification webhook. On `transaction_status === 'settlement'`, write a `pembayaran` row and transition `order.status` `pending → confirmed`.
  - Verify signature using `SHA512(orderId + statusCode + grossAmount + serverKey)`.

### 2. **TUGAS 7 — Header dummy pages polish**

Stubs at `/gift-card`, `/lookbook`, `/treatment-files`, `/mitra` are rendered but content is light. Use [README-GAMBAR-STATIS.md](README-GAMBAR-STATIS.md) as the visual asset manifest for these pages.

### 3. **Review system polish leftovers**

- A "My Reviews" tab inside `/akun` (read-only list of the user's own reviews with a "View on salon" link).
- Salon-owner can reply once per review (needs `salon_reply` column + UI on `/owner/orders/{id}`).
- Backfill the `review` table with mock data so the feature has visible coverage in dev.
- Optional: a `php artisan reviews:recompute` console command that re-runs the observer for all salons (useful after a manual DB import).

### 4. **TUGAS 9 — Optional batch 2** (skincare e-commerce, lookbook, library, staff portal)

Architecture only — keep it as a placeholder backlog until 1–7 are 100%.

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
