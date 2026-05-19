# Laporan Implementasi: Tombol Cross-Login antar Panel (Admin → Owner/Customer, Owner → Customer)

> 📌 **Status: ✅ Implementasi Selesai**
> Halaman login admin (`/admin/login`) sekarang punya 2 tombol tambahan ke owner login dan customer login. Halaman login owner (`/owner/login`) punya 1 tombol tambahan ke customer login. Tombol-tombol ini muncul di bawah form login utama, terpisah oleh divider "or sign in as / or".

---

## 1. Apa yang Diimplementasi (Ringkas)

VIYGO punya **3 panel terpisah** dengan login masing-masing:

| Panel | URL Login | Untuk siapa |
|-------|-----------|-------------|
| Admin | `/admin/login` | role `admin` — VIYGO staff |
| Owner | `/owner/login` | role `salon_owner` — pemilik salon |
| Customer | `/login` | role `customer` — end user yang booking |

Sebelumnya: 3 halaman ini **isolated** — kalau user salah masuk halaman login, harus ubah URL manual.

Sesudah: 2 halaman Filament login punya tombol shortcut ke halaman login lain. Permintaan user:

- **Admin login** → tambah tombol ke **Owner login** dan **Customer login**
- **Owner login** → tambah tombol ke **Customer login**

(Customer login `/login` tidak diubah — user tidak meminta).

---

## 2. File yang Dibuat / Diubah

| File | Status | Tujuan |
|------|--------|--------|
| [`resources/views/filament/auth/login-links-admin.blade.php`](../../resources/views/filament/auth/login-links-admin.blade.php) | **Baru** | View berisi 2 tombol untuk admin login page |
| [`resources/views/filament/auth/login-links-owner.blade.php`](../../resources/views/filament/auth/login-links-owner.blade.php) | **Baru** | View berisi 1 tombol untuk owner login page |
| [`app/Providers/Filament/AdminPanelProvider.php`](../../app/Providers/Filament/AdminPanelProvider.php) | **Diubah** | Register render hook `AUTH_LOGIN_FORM_AFTER` |
| [`app/Providers/Filament/OwnerPanelProvider.php`](../../app/Providers/Filament/OwnerPanelProvider.php) | **Diubah** | Register render hook `AUTH_LOGIN_FORM_AFTER` |

**Tidak ada migration, route baru, atau perubahan controller** — fitur ini purely UI/UX dengan memanfaatkan Filament render hook.

---

## 3. Pendekatan Teknis: Filament Render Hooks

Filament 5.6 punya **render hooks** — titik injeksi konten kustom di lokasi spesifik di layout default. Untuk halaman login:

```php
PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE  // di atas form login
PanelsRenderHook::AUTH_LOGIN_FORM_AFTER   // di bawah form login (yang kita pakai)
```

Keuntungan pakai hook vs override page class:
- **Tidak fork** built-in `Filament\Auth\Pages\Login` (update Filament tidak break)
- **Cuma tambah konten** — form login bawaan tetap utuh (email, password, remember-me, forgot-password)
- **Theming match** — pakai Tailwind classes yang sama dengan Filament default

Cara register:

```php
->renderHook(
    PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
    fn (): string => Blade::render('@include(\'filament.auth.login-links-admin\')'),
)
```

`Blade::render()` mengembalikan string HTML dari template Blade, yang di-inject Filament di tempat hook didefinisikan.

---

## 4. Detail Tiap Perubahan

### 4a. View Admin Login Links

[`resources/views/filament/auth/login-links-admin.blade.php`](../../resources/views/filament/auth/login-links-admin.blade.php) — 2 tombol:

```blade
<div class="mt-6 space-y-3">
    <div class="relative flex items-center">
        {{-- Divider "or sign in as" --}}
        <div class="flex-grow border-t border-gray-200"></div>
        <span class="mx-3 text-xs uppercase tracking-wider text-gray-400">or sign in as</span>
        <div class="flex-grow border-t border-gray-200"></div>
    </div>

    <a href="{{ url('/owner/login') }}" class="...">
        <svg>...building icon...</svg>
        Login as Salon Owner
    </a>

    <a href="{{ route('login') }}" class="...">
        <svg>...user icon...</svg>
        Login as Customer
    </a>
</div>
```

**Highlight UI:**
- Divider visual dengan label "or sign in as" untuk pisahkan dari form login utama
- 2 tombol full-width dengan border + hover state pakai `primary-500` (warna brand dari panel)
- Icon SVG inline (heroicons style) di kiri label
- Dark mode aware (`dark:` classes)

### 4b. View Owner Login Links

[`resources/views/filament/auth/login-links-owner.blade.php`](../../resources/views/filament/auth/login-links-owner.blade.php) — 1 tombol:

```blade
<div class="mt-6 space-y-3">
    <div class="relative flex items-center">
        <span class="mx-3 text-xs uppercase tracking-wider text-gray-400">or</span>
        ...
    </div>

    <a href="{{ route('login') }}" class="...">
        <svg>...user icon...</svg>
        Login as Customer
    </a>
</div>
```

Lebih singkat — divider cuma "or", 1 tombol saja ke `route('login')`.

### 4c. AdminPanelProvider

```php
// Tambahan import
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

// Method panel() — tambahkan di akhir chain
->authMiddleware([Authenticate::class])
->renderHook(
    PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
    fn (): string => Blade::render('@include(\'filament.auth.login-links-admin\')'),
);
```

### 4d. OwnerPanelProvider

Sama struktur, hanya beda view yang di-include:

```php
->renderHook(
    PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
    fn (): string => Blade::render('@include(\'filament.auth.login-links-owner\')'),
);
```

---

## 5. URL Mapping & Route Resolution

| Tombol | Code | URL Hasil |
|--------|------|-----------|
| "Login as Salon Owner" (di admin) | `url('/owner/login')` | `/owner/login` |
| "Login as Customer" (di admin) | `route('login')` | `/login` |
| "Login as Customer" (di owner) | `route('login')` | `/login` |

`route('login')` mengarah ke Laravel Fortify's login page (`AuthenticatedSessionController@create`), yang di-render dari [`resources/views/pages/auth/login.blade.php`](../../resources/views/pages/auth/login.blade.php).

---

## 6. Layout & Hierarki Visual

Halaman login Filament default punya struktur:

```
┌─────────────────────────────────────┐
│  [Brand: VIYGO Admin / Salon]       │
│  Sign in                            │
│  ─────────────────                  │
│  Email:    [_______________]        │
│  Password: [_______________]        │
│  [ ] Remember me                    │
│  Forgot password?                   │
│  ─────────────────                  │
│  [   Sign in (button)    ]          │  ← form ends here
│                                     │
│  ─── or sign in as ───              │  ← AUTH_LOGIN_FORM_AFTER hook
│  [ 🏪 Login as Salon Owner ]        │
│  [ 👤 Login as Customer    ]        │  ← admin login only
└─────────────────────────────────────┘
```

Untuk owner login struktur sama, tapi cuma tombol "Login as Customer".

---

## 7. Cara Test Manual

### Test admin login page

1. Buka browser → `https://agonize-unbraided-squealing.ngrok-free.dev/admin/login`
2. Verifikasi tampilan:
   - Form login normal (email, password, remember, forgot)
   - Divider "or sign in as" di bawah tombol Sign in
   - Tombol **"Login as Salon Owner"** dengan icon building
   - Tombol **"Login as Customer"** dengan icon user
3. Klik **"Login as Salon Owner"** → redirect ke `/owner/login` ✅
4. Kembali ke `/admin/login` → klik **"Login as Customer"** → redirect ke `/login` ✅

### Test owner login page

5. Buka `/owner/login`
6. Verifikasi:
   - Form login normal
   - Divider "or"
   - 1 tombol **"Login as Customer"**
   - **TIDAK ADA** tombol ke admin (sesuai requirement)
7. Klik **"Login as Customer"** → redirect ke `/login` ✅

### Test customer login page (tidak diubah)

8. Buka `/login`
9. Tampilan tetap seperti sebelumnya — tidak ada tombol cross-login (sesuai requirement, user tidak minta)

---

## 8. Edge Cases & Behavior

| Skenario | Behavior |
|----------|----------|
| User klik "Login as Salon Owner" tapi belum punya akun salon | Masuk `/owner/login`, login gagal "Invalid credentials" (tetap di owner login) |
| Admin yang sudah login klik link ke owner login | Browser arahkan ke `/owner/login` — Filament cek session, kalau session admin valid tapi role beda, login form tetap muncul |
| User klik tombol di mobile | Tombol full-width responsive, ikut layout Filament default |
| Dark mode aktif | Tombol pakai `dark:` Tailwind classes — border & text color adjust otomatis |
| Browser disable JavaScript | Tombol tetap berfungsi (plain `<a href>`, tidak butuh JS) |

---

## 9. Keamanan

| Concern | Status |
|---------|--------|
| Open redirect? | ❌ Tidak — semua link hardcoded ke URL relatif internal (`/login`, `/owner/login`), bukan dari user input |
| XSS via view? | ❌ Tidak — semua label hardcoded di template, tidak ada user input |
| Information disclosure (3 panel exists)? | ⚠️ Minor — link "Login as Salon Owner" mengumumkan ada panel owner. Tapi `/owner/login` sudah public/discoverable lewat URL guessing, jadi tidak menambah risiko. |
| Session leak antar panel? | ❌ Tidak — Filament pakai session terpisah per panel (`id('admin')`, `id('owner')`), session admin tidak ter-share ke owner panel |

---

## 10. Yang Bisa Di-improve Nanti (Opsional)

| Item | Effort | Catatan |
|------|--------|---------|
| Tambah tombol di customer login page (`/login`) ke admin/owner | 30 menit | Edit [`pages/auth/login.blade.php`](../../resources/views/pages/auth/login.blade.php) — tambah section tombol mirip |
| Auto-redirect berdasarkan role saat login sukses | sudah ada | [`routes/web.php:93-101`](../../routes/web.php#L93-L101) — `dashboard` route sudah redirect ke panel sesuai role |
| "Continue as guest" link untuk customer | 15 menit | Tambah link ke homepage `/` di customer login |
| Animation/transition saat hover tombol | 15 menit | Tambah Tailwind `transition-transform` & `hover:scale-[1.02]` |
| Localization (Indonesian / English toggle) | 1-2 jam | Wrap text di `__('...')` + tambah `lang/id/auth.php` |
| Remember last panel saat user kunjungan ulang | 30 menit | Set cookie `last_panel`, auto-redirect saat hit `/login` umum |

---

## 11. Yang TIDAK Diubah (sesuai requirement)

- **Customer login page (`/login`)** — user tidak minta tombol shortcut di sini
- **Logic redirect post-login** — di [`routes/web.php:94-101`](../../routes/web.php#L94-L101) sudah benar (admin → /admin, salon_owner → /owner, lainnya → /akun/bookings)
- **Form login itu sendiri** — email, password, remember-me, forgot-password tetap utuh di semua panel
- **Brand/theming masing-masing panel** — admin & owner masih pakai warna brand sendiri

---

## 12. TL;DR

1. Pakai Filament **render hook** `AUTH_LOGIN_FORM_AFTER` di kedua panel provider (admin & owner)
2. Hook inject 2 file Blade berisi tombol link ke panel login lain
3. **Admin login** → 2 tombol: "Login as Salon Owner" + "Login as Customer"
4. **Owner login** → 1 tombol: "Login as Customer"
5. **Customer login** → tidak diubah (tidak diminta)
6. Implementasi clean — tanpa migration/route/controller, hanya 2 view baru + 2 baris di setiap panel provider
7. Theming match Filament default (Tailwind + dark mode + responsive)
