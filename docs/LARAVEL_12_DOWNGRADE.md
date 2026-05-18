# Laravel 13 → 12 Downgrade Runbook

> **Audience:** an AI coding agent (or human engineer) executing the downgrade end-to-end on this repository.
> **Scope:** revert `laravel/framework` from `^13.0` to `^12.0` while keeping Filament 5, Livewire 4, Fortify, Pest 4, and the existing application code working.
> **Status of repository at time of writing:** Laravel Framework `13.7.0`, PHP `8.5.3` (local). The change of `composer.json` constraint has already been performed (see step 2). All other steps are still pending unless the runbook says otherwise.

---

## 0. TL;DR

```powershell
# from repository root
Copy-Item composer.lock composer.lock.bak
Rename-Item vendor vendor_l13_bak
composer update --with-all-dependencies
php artisan --version            # expect: Laravel Framework 12.x
php artisan optimize:clear
php artisan filament:upgrade
php artisan migrate
php artisan test
npm run build
```

If `composer update` fails, see [§6 Troubleshooting](#6-troubleshooting).

---

## 1. Preconditions

1. **OS / shell:** Windows 11, PowerShell 7+ (this repo's primary shell). Bash via WSL also works; commands below are PowerShell.
2. **Repo path:** `d:\VIYGO-FINAL` (absolute). All commands assume this is the current working directory.
3. **Tools required on PATH:**
   - `php` — 8.3 or 8.4 recommended. **PHP 8.5 works but emits deprecation warnings on L12** — see [§7 PHP version notes](#7-php-version-notes).
   - `composer` — v2.7+.
   - `node` and `npm` — for the Vite build at the end.
   - `git` — for branching/rollback.
4. **Disk:** at least ~500 MB free for `vendor_l13_bak/` + new `vendor/`.
5. **Database:** a working DB connection (per `.env`). Migration step requires DB up; if you are running in a clean clone, copy `.env.example` to `.env` and configure first.
6. **Branch state:** working tree may be dirty (it is, at the time of writing). Commit or stash unrelated changes before starting so the downgrade diff is reviewable in isolation.

```powershell
git status               # inspect
git stash push -u -m "pre-l12-downgrade"   # optional
git checkout -b chore/downgrade-laravel-12
```

---

## 2. What has already been done

Only **one** edit has been made in advance:

- **File:** `composer.json`
- **Change:** `"laravel/framework": "^13.0"` → `"laravel/framework": "^12.0"`

Everything else (composer install, cache clears, tests, build) is **not yet done**. If you re-run the runbook on a fresh clone where this edit is missing, apply it first:

```powershell
# verify
Select-String -Path composer.json -Pattern 'laravel/framework'
# expected line: "laravel/framework": "^12.0",
```

If still on `^13.0`, edit it back to `^12.0` before continuing.

---

## 3. Dependency compatibility matrix

All third-party packages already declare L12 compatibility in their `composer.json` constraints (verified against `composer.lock` at time of writing). **No other package version needs to change.**

| Package | Current constraint | Locked version | L12 OK? | Source of truth |
|---|---|---|---|---|
| `laravel/framework` | `^12.0` (after step 2) | will resolve to `12.x` | ✅ | required |
| `filament/filament` | `5.6` | `v5.6.0` | ✅ | `filament/support` requires `illuminate/contracts: ^11.28\|^12.0\|^13.0` |
| `livewire/livewire` | `^4.1` | `v4.3.0` | ✅ | requires `laravel/framework: ^10.15\|^11\|^12\|^13` |
| `livewire/flux` | `^2.13.1` | `v2.13.2` | ✅ | requires `livewire/livewire: ^3.7.4\|^4.0` |
| `laravel/fortify` | `^1.34` | `v1.36.2` | ✅ | requires `illuminate/console: ^10\|^11\|^12\|^13` |
| `laravel/tinker` | `^3.0` | `v3.0.0` | ✅ | requires `illuminate/*: ^8\|...\|^13` |
| `nunomaduro/collision` | `^8.9` | `v8.9.3` | ✅ | conflict only on `laravel/framework <11.48 \|\| >=14` |
| `pestphp/pest` | `^4.5` | `v4.5.0` | ✅ | works on L12 |
| `pestphp/pest-plugin-laravel` | `^4.1` | — | ⚠️ | requires `laravel/framework: ^11.45.2\|^12.52.0\|^13.0` — must resolve to **L12.52 or later** |
| `midtrans/midtrans-php` | `^2.6` | — | ✅ | framework-agnostic |
| `laravel/pail` | `^1.2.5` | — | ✅ | supports L12 |
| `laravel/sail` | `^1.53` | — | ✅ | dev-only |
| `laravel/pint` | `^1.27` | — | ✅ | standalone |

**Implication:** `composer update --with-all-dependencies` should resolve cleanly. If composer picks an old `12.0.x` patch, the `pest-plugin-laravel` constraint will fail — composer must select **≥ 12.52.0**. If that happens, see [§6.2](#62-pest-plugin-laravel-constraint-failure).

---

## 4. Application-code audit (no blockers found)

Files scanned: `app/`, `bootstrap/`, `routes/`, `config/`, `database/migrations/`. Every Laravel API in use already exists in **Laravel 11+**, so nothing in the app code needs to be rewritten for L12. Confirmed:

- `bootstrap/app.php` — skeleton (`Application::configure()->withRouting(health: '/up')->withMiddleware(...)`). Identical between L11/12/13.
- `app/Providers/AppServiceProvider.php`:
  - `Date::use(CarbonImmutable::class)` — L11+
  - `DB::prohibitDestructiveCommands()` — L11+
  - `Password::defaults(fn(): ?Password => ...)` — L11+
- `routes/console.php` — `Schedule::command('bookings:complete')->dailyAt('01:00')->timezone('Europe/London')` — L11+
- `routes/settings.php` — `Route::livewire(...)` (Livewire 4 macro), `when()` helper — both available.
- `bootstrap/providers.php` — explicit provider list, L11+ style.
- Middleware alias `role => CheckRole::class` and CSRF except `midtrans/webhook` registered in `bootstrap/app.php` — L11+ idiom.
- Migrations use standard Blueprint API (incl. `fullText` and composite indexes from `2026_05_16_100001`/`2026_05_16_100002`) — supported since L9.

**No L13-only API was found.** Therefore: do not rewrite controllers, models, or providers.

---

## 5. Execution steps (run in order)

### 5.1 Snapshot the current install

```powershell
Copy-Item composer.lock composer.lock.bak
Rename-Item vendor vendor_l13_bak
```

These are the rollback artifacts. Do **not** delete them until the downgrade is verified.

### 5.2 Resolve dependencies for L12

```powershell
composer update --with-all-dependencies
```

This re-resolves the full graph. Expected outcome: `laravel/framework` drops to `12.x` (≥ 12.52); Filament/Livewire/Fortify stay on their current major versions.

If composer prompts about removing packages, **read the prompt** — only `laravel/framework` should change major version. Anything else changing major (e.g., Filament dropping to 4.x) is a red flag — abort and consult [§6](#6-troubleshooting).

### 5.3 Verify framework version

```powershell
php artisan --version
```

Expected: `Laravel Framework 12.x.y` (where `x ≥ 52`). If you see `13.x` still, composer did not apply the new constraint — re-check `composer.json` and re-run §5.2.

### 5.4 Clear caches and re-run package bootstrap

```powershell
php artisan optimize:clear
php artisan package:discover --ansi
php artisan filament:upgrade
```

`filament:upgrade` is already wired into `post-autoload-dump` in `composer.json`, so it usually runs automatically during `composer update`. Run it explicitly here as a safety net.

### 5.5 Apply migrations

```powershell
php artisan migrate
```

- For **dev** with safe data: `php artisan migrate:fresh --seed` is acceptable. Note `DB::prohibitDestructiveCommands(app()->isProduction())` will block `fresh`/`wipe` only when `APP_ENV=production`.
- For **prod**: only `migrate`. Never `migrate:fresh`.

### 5.6 Build frontend

```powershell
npm install
npm run build
```

Vite/Tailwind/Alpine versions are independent of the Laravel major version, so no changes expected.

### 5.7 Smoke tests

Run all of these:

```powershell
php artisan test                 # Pest suite must pass
php artisan route:list           # no errors; verify settings.* routes exist
php artisan schedule:list        # verify bookings:complete daily 01:00 Europe/London
php artisan tinker --execute="echo app()->version();"
```

Then manually (or via headless browser) verify:

| Surface | Route(s) | What to check |
|---|---|---|
| Home / public | `/` | Page renders; categories load. |
| Auth | Fortify routes (`/login`, `/register`, `/two-factor-*`) | Login + 2FA flow works. |
| Akun | `/akun/...` (`AkunController`) | Profile + bookings view. |
| Booking | `/booking/create` (`BookingController`) | Slot generation, create booking. |
| Payment | `/midtrans/webhook` (signed POST), payment redirects | Webhook accepts signed payload; CSRF is bypassed for `midtrans/webhook`. |
| Filament Admin | `/admin` (`AdminPanelProvider`) | Login + at least one resource list renders. |
| Filament Owner | `/owner` (`OwnerPanelProvider`) | Same. |
| Livewire pages | `/settings/profile`, `/settings/security`, `/settings/appearance` | All three render; 2FA confirm password gate respected. |
| Health | `/up` | Returns 200. |

### 5.8 Commit

```powershell
git add composer.json composer.lock
git commit -m "chore: downgrade laravel/framework to ^12.0"
```

If you also regenerated lockfiles, include them. Do **not** commit `vendor_l13_bak/` or `composer.lock.bak` — add to `.gitignore` if necessary, or just delete after verification (see §8).

---

## 6. Troubleshooting

### 6.1 `composer update` fails with "Your requirements could not be resolved"

1. Read the full error. Identify which package is blocking.
2. Most common cause: a transitive package was hard-locked. Fix:
   ```powershell
   Remove-Item composer.lock
   composer install
   ```
   If composer still cannot install, run `composer why-not laravel/framework 12.52` to see who is blocking L12.
3. If a specific package is too new and rejects L12 (none expected at time of writing), pin it down to its previous compatible version, e.g.:
   ```powershell
   composer require "filament/filament:^5.6" "livewire/livewire:^4.1" --update-with-all-dependencies
   ```

### 6.2 `pest-plugin-laravel` constraint failure

Symptom: composer says `pestphp/pest-plugin-laravel ^4.1` requires `laravel/framework ^11.45.2|^12.52.0|^13.0` but resolved `laravel/framework 12.0.x`.

Fix: tighten the framework constraint in `composer.json`:

```json
"laravel/framework": "^12.52"
```

Then `composer update --with-all-dependencies` again.

### 6.3 PHP version errors

If `composer update` rejects packages due to PHP version, see [§7](#7-php-version-notes).

### 6.4 Filament panels error after upgrade

```powershell
php artisan filament:upgrade
php artisan filament:cache-components
php artisan optimize:clear
```

If admin/owner panel still errors, dump the provider list to confirm both panel providers are present:

```powershell
Get-Content bootstrap\providers.php
```

Expected entries: `AppServiceProvider`, `Filament\AdminPanelProvider`, `Filament\OwnerPanelProvider`, `FortifyServiceProvider`.

### 6.5 Livewire macro `Route::livewire(...)` undefined

Means Livewire's service provider didn't register. Run:

```powershell
php artisan package:discover --ansi
php artisan optimize:clear
```

### 6.6 `php artisan test` regression

Pest 4 + L12 supported. If a specific test fails, it's almost certainly a project-level issue (DB state, time-sensitive logic, Midtrans test mode). Don't roll back the framework over a single test — investigate the test.

---

## 7. PHP version notes

| PHP | L12 support | Notes |
|---|---|---|
| 8.2 | ✅ minimum | |
| 8.3 | ✅ recommended | matches repo constraint `php: ^8.3` |
| 8.4 | ✅ | works |
| 8.5 | ⚠️ unofficial | repo's local box is on 8.5; expect deprecation notices, not hard failures |

If composer balks on PHP 8.5, you can soft-bypass with `--ignore-platform-req=php` for installation, but **do not** ship to production on an unsupported PHP. Prefer installing PHP 8.3/8.4 locally.

---

## 8. Post-verification cleanup

Only after **all** of §5.7 passes:

```powershell
Remove-Item -Recurse -Force vendor_l13_bak
Remove-Item composer.lock.bak
```

---

## 9. Rollback procedure

If anything in §5.7 fails and cannot be reasonably fixed forward:

```powershell
# 1. restore vendor + lock
Remove-Item -Recurse -Force vendor
Move-Item vendor_l13_bak vendor
Copy-Item composer.lock.bak composer.lock -Force

# 2. restore composer.json constraint
# edit composer.json: "laravel/framework": "^12.0"  →  "^13.0"

# 3. confirm
php artisan --version          # expect 13.x again
php artisan optimize:clear
```

Optionally revert the git branch:

```powershell
git checkout main
git branch -D chore/downgrade-laravel-12
```

---

## 10. Definition of Done

The downgrade is considered complete when **all** of the following hold:

- [ ] `php artisan --version` reports `Laravel Framework 12.x` (x ≥ 52).
- [ ] `composer.json` shows `"laravel/framework": "^12.0"` (or `^12.52`).
- [ ] `composer.lock` is regenerated and committed.
- [ ] `composer update` ran without resolver errors.
- [ ] `php artisan test` is green.
- [ ] `php artisan route:list` runs without errors and shows expected routes (`/up`, `/admin`, `/owner`, settings routes, Midtrans webhook).
- [ ] `php artisan schedule:list` shows `bookings:complete` at `01:00 Europe/London`.
- [ ] `npm run build` succeeds.
- [ ] Filament admin + owner panels load and authenticate.
- [ ] Midtrans webhook endpoint still bypasses CSRF and processes a signed test payload.
- [ ] Booking creation path (`/booking/create`) works end-to-end on dev DB.

If any item is unchecked, treat the downgrade as **incomplete** and either fix forward or roll back per §9.

---

## 11. Files changed by this downgrade

Minimal expected diff:

```
composer.json     # 1 line: laravel/framework constraint
composer.lock     # regenerated by composer
```

No application source, migration, view, or config file needs to change. If an agent finds itself editing controllers/models/services to "make L12 work," **stop** — the cause is almost certainly elsewhere (env, cache, package resolution), not the application code.

---

## 12. Reference: what was *not* changed and why

| Item | Why untouched |
|---|---|
| Filament 5.6 | Explicitly compatible with L12 via `illuminate/contracts: ^11.28\|^12.0\|^13.0`. |
| Livewire 4.x | Compatible with `^10.15\|^11\|^12\|^13`. |
| Fortify 1.36 | Compatible with `^10\|^11\|^12\|^13`. |
| Pest 4 | Compatible with L12. |
| `bootstrap/app.php` | L11+ skeleton, identical across L11/12/13. |
| `app/Providers/*` | Only uses APIs that exist in L11+. |
| Migrations | Standard Blueprint API since L9. |
| Frontend (`vite`, `tailwindcss v4`, `alpinejs`) | Independent of Laravel major version. |

---

**End of runbook.**
