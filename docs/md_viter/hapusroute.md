# Laporan: Hapus Fitur Gift Card, Lookbook, Treatment Files, VIYGO Rewards, Wallet, Refer a Friend

> 📌 **Status: ✅ Implementasi Selesai**
> Membersihkan 6 fitur dari frontend & backend: 3 nav menu (Gift Card, Lookbook, Treatment Files) dari navbar dan 3 card (VIYGO Rewards, Wallet, Refer a Friend) dari akun dashboard. Sekaligus hapus route, controller, view, dan import statement yang sudah tidak dipakai.

---

## 1. Yang Dihapus (Ringkas)

### A. Dari Navbar publik (`viygo-navbar.blade.php`)
- ❌ **GIFT CARD**
- ❌ **LOOKBOOK**
- ❌ **TREATMENT FILES**

### B. Dari Akun Dashboard (`akun/index.blade.php`)
- ❌ Hero banner **"VIYGO REWARDS — 0 / 1500 points"** beserta tombol "Earn Points"
- ❌ Card **"VIYGO Rewards"**
- ❌ Card **"Wallet"**
- ❌ Card **"Refer a Friend"**

### C. Routes (`routes/web.php`)
- ❌ `GET /gift-card` → `gift-card`
- ❌ `GET /lookbook` → `lookbook`
- ❌ `GET /treatment-files` → `treatment-files`
- ❌ `GET /akun/reward` → `akun.reward`

### D. Controllers (full files)
- ❌ `app/Http/Controllers/GiftCardController.php`
- ❌ `app/Http/Controllers/LookbookController.php`
- ❌ `app/Http/Controllers/TreatmentFilesController.php`
- ❌ Method `AkunController::reward()` (controller file tetap ada)

### E. Views (full directories)
- ❌ `resources/views/gift-card/` (folder + `index.blade.php`)
- ❌ `resources/views/lookbook/` (folder + `index.blade.php`)
- ❌ `resources/views/treatment-files/` (folder + `index.blade.php`)
- ❌ `resources/views/akun/reward.blade.php` (single file)

### F. Footer (`viygo-footer.blade.php`)
- ❌ Link **"Blog"** (yang sebelumnya ke `route('treatment-files')`) — link broken setelah route dihapus, jadi ikut dibersihkan

### G. Import statement (`routes/web.php` use)
- ❌ `use App\Http\Controllers\GiftCardController;`
- ❌ `use App\Http\Controllers\LookbookController;`
- ❌ `use App\Http\Controllers\TreatmentFilesController;`

---

## 2. Files yang Diubah (Tidak Dihapus)

| File | Apa yang diubah |
|------|-----------------|
| [`routes/web.php`](../../routes/web.php) | Hapus 3 import + 4 route definition |
| [`resources/views/components/viygo-navbar.blade.php`](../../resources/views/components/viygo-navbar.blade.php) | Hapus 3 `<a>` tag nav menu |
| [`resources/views/components/viygo-footer.blade.php`](../../resources/views/components/viygo-footer.blade.php) | Hapus 1 `<li>` link "Blog" |
| [`resources/views/akun/index.blade.php`](../../resources/views/akun/index.blade.php) | Hapus VIYGO Rewards banner + 3 entri `$items` array |
| [`app/Http/Controllers/AkunController.php`](../../app/Http/Controllers/AkunController.php) | Hapus method `reward()` |

---

## 3. Files yang Dihapus Total

| File | Alasan |
|------|--------|
| `app/Http/Controllers/GiftCardController.php` | Route gift-card sudah dihapus, controller tidak punya konsumer lain |
| `app/Http/Controllers/LookbookController.php` | Idem untuk lookbook |
| `app/Http/Controllers/TreatmentFilesController.php` | Idem untuk treatment-files |
| `resources/views/gift-card/index.blade.php` | View tidak punya konsumer (controller-nya sudah dihapus) |
| `resources/views/lookbook/index.blade.php` | Idem |
| `resources/views/treatment-files/index.blade.php` | Idem |
| `resources/views/akun/reward.blade.php` | Method `AkunController::reward()` dihapus, view-nya juga ikut |

---

## 4. Detail Perubahan

### 4a. Navbar — sebelum vs sesudah

**Sebelum** (di [`viygo-navbar.blade.php:191-210`](../../resources/views/components/viygo-navbar.blade.php#L191-L210)):
```blade
<a href="{{ route('gift-card') }}">Gift Card</a>
<a href="{{ route('lookbook') }}">Lookbook</a>
<a href="{{ route('treatment-files') }}">Treatment Files</a>
<a href="{{ route('mitra') }}">For Salons</a>
```

**Sesudah:**
```blade
<a href="{{ route('mitra') }}">For Salons</a>
```

→ Cuma menu "For Salons" yang tersisa di section tersebut.

### 4b. Akun Dashboard — sebelum vs sesudah

**Sebelum** (header + grid 6 items):
```
[Avatar] [Nama]                            [VIYGO REWARDS 0/1500] [Earn Points]
─────────────────────────────────────────
[📅 My Bookings]      [👤 Personal Info]
[❤️ Favourites]       [🎁 VIYGO Rewards]
[💳 Wallet]           [👥 Refer a Friend]
```

**Sesudah** (header tanpa rewards + grid 3 items):
```
[Avatar] [Nama]
─────────────────────────────────────────
[📅 My Bookings]      [👤 Personal Info]
[❤️ Favourites]
```

Code-nya:
```php
$items = [
    ['icon'=>'📅', 'title'=>'My Bookings',     ...],
    ['icon'=>'👤', 'title'=>'Personal Info',   ...],
    ['icon'=>'❤️', 'title'=>'Favourites',      ...],
    // Dihapus: VIYGO Rewards, Wallet, Refer a Friend
];
```

### 4c. Routes — sebelum vs sesudah

**Sebelum** (line 28-30 + line 86):
```php
Route::get('/gift-card', [GiftCardController::class, 'index'])->name('gift-card');
Route::get('/lookbook', [LookbookController::class, 'index'])->name('lookbook');
Route::get('/treatment-files', [TreatmentFilesController::class, 'index'])->name('treatment-files');
...
Route::get('/reward', [AkunController::class, 'reward'])->name('reward');
```

**Sesudah:** 4 baris di atas dihapus total. Import 3 controller juga dihapus.

### 4d. Controller AkunController — method dihapus

**Sebelum** (lines 112-124):
```php
/**
 * Loyalty + claimed promos.
 */
public function reward()
{
    $promos = auth()->user()
        ->promos()
        ->orderByPivot('is_used', 'asc')
        ->orderBy('time_expired', 'asc')
        ->get();

    return view('akun.reward', compact('promos'));
}
```

**Sesudah:** method dihapus. Class `AkunController` masih ada dengan method lain (index, bookings, favorit, pengaturan, updatePengaturan).

---

## 5. Reference Lain yang Dicek (No Broken Links)

Setelah hapus, saya scan untuk pastikan tidak ada code lain yang masih reference route/file yang sudah dihapus:

### Cek 1: route('...') references
```bash
grep -r "route('(gift-card|lookbook|treatment-files|akun\.reward)')"
```
Hasil: **0 match** di `app/` dan `resources/` ✅

### Cek 2: Controller class references
```bash
grep -r "GiftCardController|LookbookController|TreatmentFilesController"
```
Hasil: **0 match** di `app/` ✅

(Match ada di `docs/` archive — tapi itu file dokumentasi lama, tidak active code.)

### Cek 3: route:list
```bash
php artisan route:list | grep -iE "gift|lookbook|treatment-files|reward"
```
Hasil: **No output** — route benar-benar hilang dari registry ✅

---

## 6. Cara Verifikasi

1. **Buka homepage** `/` → navbar tidak ada "Gift Card / Lookbook / Treatment Files"
2. **Login customer** → buka `/akun` → cuma ada 3 card: My Bookings, Personal Info, Favourites
3. **Coba akses URL lama** (broken intentionally):
   - `/gift-card` → **404**
   - `/lookbook` → **404**
   - `/treatment-files` → **404**
   - `/akun/reward` → **404**
4. **Buka footer** di homepage → kolom "Company" tidak ada link "Blog"
5. **Tidak ada error 500** di halaman manapun (semua reference sudah dibersihkan)

---

## 7. Cache yang Dibersihkan

Setelah perubahan, dibersihkan dengan:
```bash
php artisan route:clear   # buang route cache lama
php artisan view:clear    # buang compiled views lama
```

Kalau Anda pakai `config:cache`, perlu juga `php artisan config:clear`.

---

## 8. Implikasi & Catatan

### Tabel database TIDAK diubah
- Tabel `user_promo` (M:N user ↔ promo, dipakai oleh fitur reward) **tetap ada**
- Method `User::promos()` relasi belongsToMany ke `Promo` via `user_promo` **tetap ada**
- Migration `create_user_promo_table` **tidak di-revert**

→ Kalau ke depan ingin restore fitur reward, struktur DB-nya siap.

### Model relasi tetap ada
- `User::promos()` di [`User.php:122-127`](../../app/Models/User.php#L122-L127) masih intact
- Promo claim flow (via `user_promo.is_used`) di booking belum di-implement, jadi tidak ada code lain yang pecah

### Yang TIDAK dihapus
- **Model `Promo`** — masih dipakai owner-created promo (dari fitur sebelumnya di [`diskonowner.md`](diskonowner.md))
- **Admin PromoResource & Owner PromoResource** — masih full functional
- **Booking promo flow** — `BookingController::validatePromo`, kolom `order.id_promo` tetap berfungsi
- **`User::promos()` relation** — kalau nanti ada fitur claim/reward bisa di-revive

### Risiko
- Kalau ada bookmark customer ke `/akun/reward` → 404
- Kalau ada email lama dengan link `/gift-card` atau `/lookbook` → 404
- Search engine yang sudah index URL ini akan dapat 404 — bisa di-set 301 redirect ke `/` kalau perlu (TODO)

---

## 9. Yang Bisa Ditambahkan Nanti (Opsional)

| Item | Effort | Catatan |
|------|--------|---------|
| 301 redirect URL lama ke `/` (SEO) | 15 menit | Tambah `Route::redirect('/gift-card', '/', 301)` di web.php |
| Migrate revert `user_promo` table | 10 menit | `php artisan migrate:rollback --path=...` — tapi tidak perlu kalau tidak ada data |
| Hapus `User::promos()` & migration `user_promo` | 30 menit | Hanya kalau yakin tidak akan revive fitur reward |
| Cleanup `docs/archive/update/` yang masih reference | 5 menit | File arsip, tidak active code — opsional |

---

## 10. TL;DR

- Hapus **3 nav menu** (Gift Card, Lookbook, Treatment Files) dari navbar
- Hapus **3 card** (Wallet, Refer a Friend, VIYGO Rewards) + hero banner reward dari akun dashboard
- Hapus **4 routes** dan **3 controller files** + 1 method controller
- Hapus **4 view files** (3 folder + reward.blade.php)
- Hapus **1 link footer** ("Blog") yang reference route lama
- Hapus **3 import statement** controller di web.php
- Verifikasi: tidak ada broken reference, tidak ada syntax error, route:list confirm
- Tabel `user_promo` + model relasi `User::promos()` **tetap ada** (siap di-revive kalau perlu)
