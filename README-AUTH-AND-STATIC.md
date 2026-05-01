# README — Auth Branding (TUGAS 6) + Static Pages (TUGAS 3)

> **Sessions:** 2 May 2026.
> **Branch:** *(this repo is not a git repository at the moment; if it ever is, this work would belong on `feature/auth-branding-and-static-pages`.)*
> **Companion doc:** [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md), [README-GAMBAR-STATIS.md](README-GAMBAR-STATIS.md)

---

## TUGAS 6 — Auth page branding ✅

### What changed

| File | Action | Notes |
|------|--------|-------|
| `resources/views/layouts/auth.blade.php` | **MODIFIED** | Now wraps `<x-layouts::auth.split>` instead of `simple` so all Fortify auth pages get the new 2-column VIYGO design. |
| `resources/views/layouts/auth/split.blade.php` | **REWRITTEN** | Left panel: VIYGO navy gradient + radial glow + tagline "Your next great beauty moment starts here." + small stats line. Right panel: form. Mobile fallback shows logo+brand only. Loads DM Sans / DM Serif Display from Google Fonts. Defines `--viygo-navy` and `--viygo-blue` CSS vars at layout scope. |
| `resources/views/components/auth-header.blade.php` | **REWRITTEN** | Replaced Flux `<flux:heading>` / `<flux:subheading>` with a serif `<h1>` + muted `<p>` matching VIYGO typography. |
| `resources/views/pages/auth/login.blade.php` | **MODIFIED** | Title/description now: "Welcome back" / "Sign in to book your next beauty appointment". |
| `resources/views/pages/auth/register.blade.php` | **MODIFIED** | Title/description now: "Join VIYGO" / "Create your free account and discover beauty pros near you". |
| `resources/views/components/viygo-navbar.blade.php` | **MODIFIED** | "My Account" link now branches on `auth()->user()->role` → `customer` goes to `/akun`, `salon_owner` goes to `/owner`, `admin` goes to `/admin`, with localized labels ("My Account" / "Salon Dashboard" / "Admin Panel"). |

### Logo asset usage
- `public/icon.png` — used as the small mark in the brand panel (40x40 rounded) and as the mobile-only logo.
- `public/dark.png` and `public/white.png` were intentionally **not** used in the auth split because the brand panel already has a navy background, and a stand-alone wordmark would clutter the hero text. They remain available for the public navbar/footer (footer already uses `white.png`).

### Status / not-yet-done
- The Fortify register form still posts a `name` field (single string) and our `User` model uses `first_name + last_name`. **Pre-existing mismatch — not introduced by this work.** Fortify's `CreateNewUser` action needs splitting `name` into first/last, OR the register form needs updating to first/last fields. → File a follow-up.
- The "split" layout's blockquote (Inspiring quote) was removed — replaced by VIYGO marketing copy.

### Verification
- Visit `/login` → should see split layout with VIYGO navy panel left, form right, "Welcome back" header.
- Visit `/register` → same layout, "Join VIYGO" header.
- Resize to mobile: brand panel hides, mobile logo+brand stack appears above form.

---

## TUGAS 3 — Static pages ✅

### What changed

| File | Action |
|------|--------|
| `app/Http/Controllers/StaticController.php` | **NEW** — 8 controller actions: `about`, `careers`, `press`, `help`, `contact`, `privacy`, `terms`, `cookies`. `help` and `contact` receive a `$supportEmail` view-data prop from `config('viygo.support_email')`. |
| `config/viygo.php` | **NEW** — central config for `support_email`, `help_email`, and `social.{instagram,facebook,tiktok}` URLs. All env-overridable. |
| `routes/web.php` | **MODIFIED** — added 8 named routes `static.about/careers/press/help/contact/privacy/terms/cookies` after `/mitra`. |
| `resources/views/static/about.blade.php` | **NEW** — hero + mission/story/promise + by-the-numbers + CTA. Uses VIYGO public layout. Image placeholder reserved for `img-about-hero`. |
| `resources/views/static/careers.blade.php` | **NEW** — hero + 5 example open roles with mailto Apply buttons (subject prefilled). |
| `resources/views/static/press.blade.php` | **NEW** — news listing + press@viygo.com block. |
| `resources/views/static/help.blade.php` | **NEW** — 6 FAQ accordion (Alpine native `<details>`) + `support_email` prominent + link to `/contact`. |
| `resources/views/static/contact.blade.php` | **NEW** — channels (support / partners / press) + simple message form (action `#`, **submission target TBD**). |
| `resources/views/static/privacy.blade.php` | **NEW** — UK GDPR-flavored policy in 7 sections. |
| `resources/views/static/terms.blade.php` | **NEW** — marketplace T&Cs in 9 sections. |
| `resources/views/static/cookies.blade.php` | **NEW** — cookie policy with table of `viygo_session`, `XSRF-TOKEN`, `_ga`. |
| `resources/views/components/viygo-footer.blade.php` | **MODIFIED** — Company column now links to real routes (About, Careers, Blog→`treatment-files`, Press, List your salon). Help column links to all five legal/help routes. Social media buttons read URLs from `config('viygo.social')` and open `target="_blank" rel="noopener"`. |
| `README-GAMBAR-STATIS.md` | **NEW** — image-spec manifest for AI image-generation agent: 24 image IDs across hero, careers, press, help/contact, lookbook, treatment-files, mitra, gift-card. Includes prompt, dimensions, format, location. |

### Logic / workaround
- Static pages return plain views — they don't carry any DB queries.
- The Contact form has no backend handler yet; submission target is `#` so submitting is a no-op. The view already includes a fallback "or just email us" link to `support@viygo.com` as the practical channel until a handler is wired up.
- Privacy/Terms/Cookies bodies are **placeholder copy** modelled on UK GDPR/marketplace T&C structure. **NOT legal advice.** A real lawyer must review before launch.
- `config/viygo.php` is loaded automatically by Laravel — no further registration needed.

### Status / not-yet-done
- All views use placeholder gradients/emojis where real photography belongs. → See [README-GAMBAR-STATIS.md](README-GAMBAR-STATIS.md) for the full image manifest and the post-image-generation tasks checklist.
- Contact form has no `POST /contact` handler. Add `StaticController::submitContact` that emails `support_email` (Laravel Mail or queued job) when wiring the form is needed.
- Footer social URLs default to `https://instagram.com/viygo` etc. Update `.env` with real URLs (`VIYGO_INSTAGRAM_URL=…`).

### Verification
- `php artisan route:list | grep static` → 8 named routes listed.
- Visit `/about`, `/careers`, `/press`, `/help`, `/contact`, `/privacy`, `/terms`, `/cookies` — each renders without 500.
- Footer "Company" and "Help" columns now have working links (no `href="#"`).
- Help/Contact pages display `support@viygo.com` (override via `VIYGO_SUPPORT_EMAIL=...` in `.env`).
- Footer social icons → on click, open `https://instagram.com/viygo` (or `.env`-overridden URL) in a new tab.

---

## Files summary

### Created
```
d:\VIYGO-GO\VIYGO\app\Http\Controllers\StaticController.php
d:\VIYGO-GO\VIYGO\config\viygo.php
d:\VIYGO-GO\VIYGO\resources\views\static\about.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\careers.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\press.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\help.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\contact.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\privacy.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\terms.blade.php
d:\VIYGO-GO\VIYGO\resources\views\static\cookies.blade.php
d:\VIYGO-GO\VIYGO\README-GAMBAR-STATIS.md
d:\VIYGO-GO\VIYGO\README-AUTH-AND-STATIC.md           ← this file
```

### Modified
```
d:\VIYGO-GO\VIYGO\resources\views\layouts\auth.blade.php
d:\VIYGO-GO\VIYGO\resources\views\layouts\auth\split.blade.php
d:\VIYGO-GO\VIYGO\resources\views\components\auth-header.blade.php
d:\VIYGO-GO\VIYGO\resources\views\pages\auth\login.blade.php
d:\VIYGO-GO\VIYGO\resources\views\pages\auth\register.blade.php
d:\VIYGO-GO\VIYGO\resources\views\components\viygo-navbar.blade.php
d:\VIYGO-GO\VIYGO\resources\views\components\viygo-footer.blade.php
d:\VIYGO-GO\VIYGO\routes\web.php
```

---

## Next Action — for the next AI agent

In priority order, what's still on `prompt-next.md`:

1. **TUGAS 8 — Customer panel polish** (smaller scope, finish customer-side first):
   - Wishlist: create `user_favourites` migration (pivot `id_user`, `id_salon`), `User::favourites()` relation, `AkunController::toggleFavorit($salon)` POST endpoint, hook the heart icon in `salon-card.blade.php`. The route stub `akun.favorit.toggle` was removed from `routes/web.php` — re-add when controller method exists.
   - `/akun/reward`: load `auth()->user()->promos()` from the `user_promo` pivot and display real coupons with `is_used` state and expiry.
   - Order cancellation rule: in `BookingController::batal`, only allow cancellation if `$order->date_order >= today()` (existing `where('status', 'pending')` already prevents canceling completed/cancelled orders).
   - Verify `/akun/bookings` data flow end-to-end with a fixture booking.

2. **TUGAS 5 — Review system:**
   - Form on `/akun/bookings` for orders with `status = success` and no existing review (`->whereDoesntHave('review')`).
   - `ReviewController::store` posts `id_order, rating (1-5), komentar`. Use `DB::transaction` to (a) `Review::create([...])` and (b) recompute `salons.rating` (avg of `reviews.rating` where `is_visible = true`) and `salons.total_review` (count). Save with `saveQuietly()` to avoid bumping `updated_at`.
   - Consider an observer on `Review::saved/deleted` so admin moderation flips also recompute the aggregate.

3. **TUGAS 4 — Smart booking + Midtrans:**
   - Replace the static 14-slot grid in `booking/create.blade.php` with a server-side time-slot generator that:
     - Reads `salon.opening_time / closing_time`
     - Reads `staff_schedule` for any staff that can deliver the chosen service
     - Filters out slots overlapping any `OrderDetail` where `id_staff = ?` and `start_time/end_time` overlap and `status != 'canceled'`
   - For Midtrans Snap: research recent Snap docs (the user explicitly said do a web-search). Add `midtrans/midtrans-php` via Composer, create `PaymentController` with `createSnapToken($order)` and `webhook` endpoint, store `pembayaran` row on success, transition `order.status` from `pending` → `success` (or new `confirmed` if business decides).

4. **TUGAS 2 — Owner Filament panel:**
   - See "Next Action" in [README-ROLE-MIDDLEWARE.md](README-ROLE-MIDDLEWARE.md) for full breakdown. This is the largest remaining task.

5. **Update progress.md** to flip the `[ ]` checkboxes for tasks 1, 3, 6 in the bottom "YANG MASIH PERLU DIKERJAKAN" section.

---

**Author:** Claude (Opus 4.7) — 2 May 2026.
