# Phase 1D — Install Packages, Config, & Update Navigasi
## Step 1.6: DomPDF + Config Ongkir + Step 1.7: Update Navigasi

> **Prerequisite:** Phase 1A selesai (migration done)  
> **Bisa dikerjakan paralel dengan 1B dan 1C**  
> **Verifikasi:** DomPDF tersedia, config ongkir terbaca, navbar tampilkan menu baru

---

## STEP 1.6 — Install Package & Config

### 1. Install `barryvdh/laravel-dompdf`

```bash
composer require barryvdh/laravel-dompdf
```

Publish config (opsional):
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### 2. Buat `config/ongkir.php`

```php
<?php

return [
    'api_key'               => env('API_CO_ID_KEY'),
    'base_url'              => 'https://api.co.id',
    'origin_city'           => env('ONGKIR_ORIGIN_CITY', 'Jakarta Selatan'),
    'free_ongkir_threshold' => 500000, // Rp 500.000
    'couriers'              => ['jne', 'jnt', 'sicepat', 'pos'],
    'timeout_seconds'       => 5,
    'cache_ttl_minutes'     => 60, // cache hasil cek ongkir selama 1 jam
];
```

### 3. Update `.env.example`

Tambahkan baris berikut ke `.env.example`:

```
# api.co.id Expedition Cost API
API_CO_ID_KEY=
ONGKIR_ORIGIN_CITY="Jakarta Selatan"
```

Tambahkan juga ke `.env` lokal dengan nilai yang sesuai.

### Verifikasi Step 1.6

```bash
php artisan tinker
>>> config('ongkir.api_key')        # → nilai dari .env
>>> config('ongkir.origin_city')    # → "Jakarta Selatan"
>>> class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)  # → true
```

---

## STEP 1.7 — Update Navigasi

### Navbar Utama (Desktop + Mobile)

Temukan file layout navbar utama (biasanya `resources/views/components/viygo-navbar.blade.php` atau `resources/views/layouts/public.blade.php`).

**Tambahkan link baru di navbar:**

```html
<!-- Tambahkan setelah link yang sudah ada -->
<a href="{{ route('shop.index') }}" 
   class="{{ request()->is('shop*') ? 'active' : '' }}">
    🧴 Shop
</a>

<a href="{{ route('lookbook.index') }}" 
   class="{{ request()->is('lookbook*') ? 'active' : '' }}">
    📸 Lookbook
</a>

<a href="{{ route('komunitas.index') }}" 
   class="{{ request()->is('komunitas*') ? 'active' : '' }}">
    💬 Komunitas
</a>

<a href="{{ route('emptyReturn.index') }}" 
   class="{{ request()->is('empty-return*') ? 'active' : '' }}">
    ♻️ Empty Return
</a>
```

**Tambah cart badge di navbar (untuk user yang login):**

```html
@auth
<a href="{{ route('shop.cart') }}" class="relative">
    🛒
    @php $cartCount = auth()->user()->cartItems()->sum('qty') @endphp
    @if($cartCount > 0)
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
            {{ $cartCount }}
        </span>
    @endif
</a>
@endauth
```

### Menu Akun (Sidebar / Dropdown)

Temukan file sidebar akun (biasanya `resources/views/akun/` atau komponen navbar akun).

**Tambahkan menu baru di panel akun:**

```html
<!-- Tambahkan di sidebar/menu akun -->
<a href="{{ route('shop.pesanan.index') }}">🛒 Pesanan Produk</a>
<a href="{{ route('shop.wishlist') }}">❤️ Wishlist Produk</a>
<a href="{{ route('akun.poin') }}">💰 Poin & Reward</a>
<a href="{{ route('akun.bookmarks') }}">🔖 Bookmark Forum</a>
```

### Mobile Bottom Tab Bar

Tambahkan bottom navigation bar yang hanya muncul di mobile (< 768px).

Buat atau update komponen (misalnya `resources/views/components/mobile-bottom-nav.blade.php`):

```html
<!-- Mobile Bottom Tab Bar — hanya tampil di < 768px -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
    <div class="flex justify-around items-center h-16">
        <a href="{{ route('home') }}" 
           class="flex flex-col items-center text-xs {{ request()->routeIs('home') ? 'text-primary' : 'text-gray-500' }}">
            <span class="text-xl">🏠</span>
            <span>Home</span>
        </a>
        <a href="{{ route('shop.index') }}" 
           class="flex flex-col items-center text-xs {{ request()->is('shop*') ? 'text-primary' : 'text-gray-500' }}">
            <span class="text-xl">🧴</span>
            <span>Shop</span>
        </a>
        <a href="{{ route('lookbook.index') }}" 
           class="flex flex-col items-center text-xs {{ request()->is('lookbook*') ? 'text-primary' : 'text-gray-500' }}">
            <span class="text-xl">📸</span>
            <span>Lookbook</span>
        </a>
        <a href="{{ route('komunitas.index') }}" 
           class="flex flex-col items-center text-xs {{ request()->is('komunitas*') ? 'text-primary' : 'text-gray-500' }}">
            <span class="text-xl">💬</span>
            <span>Forum</span>
        </a>
        <a href="{{ auth()->check() ? route('akun.index') : route('login') }}" 
           class="flex flex-col items-center text-xs {{ request()->is('akun*') ? 'text-primary' : 'text-gray-500' }}">
            <span class="text-xl">👤</span>
            <span>Akun</span>
        </a>
    </div>
</nav>

<!-- Spacer agar konten tidak tertutup tab bar di mobile -->
<div class="h-16 md:hidden"></div>
```

Include komponen ini di layout utama `public.blade.php`:
```html
@include('components.mobile-bottom-nav')
```

### Hamburger Menu (Mobile Drawer)

Di dalam drawer hamburger menu, tambahkan link tambahan:

```html
<a href="{{ route('shop.skincareFinder') }}">🔬 Skincare Finder</a>
<a href="{{ route('emptyReturn.index') }}">♻️ Empty Return</a>
<a href="{{ route('exclusive.index') }}">🔒 Konten Eksklusif</a>
@auth
    <a href="{{ route('akun.poin') }}">💰 Poin & Reward</a>
@endauth
```

---

## PLACEHOLDER ROUTES

Karena controller-controller V2 belum ada saat ini, tambahkan placeholder routes di `routes/web.php` agar navigasi tidak error:

```php
// Placeholder routes V2 — akan di-replace saat implementasi masing-masing modul
Route::get('/shop', fn() => view('coming-soon'))->name('shop.index');
Route::get('/shop/wishlist', fn() => view('coming-soon'))->name('shop.wishlist');
Route::get('/shop/pesanan', fn() => view('coming-soon'))->name('shop.pesanan.index');
Route::get('/empty-return', fn() => view('coming-soon'))->name('emptyReturn.index');
Route::get('/komunitas', fn() => view('coming-soon'))->name('komunitas.index');
Route::get('/eksklusif', fn() => view('coming-soon'))->name('exclusive.index');
Route::get('/akun/poin', fn() => view('coming-soon'))->name('akun.poin');
Route::get('/akun/bookmarks', fn() => view('coming-soon'))->name('akun.bookmarks');
```

Buat view `resources/views/coming-soon.blade.php` sederhana:
```html
@extends('layouts.public')
@section('content')
<div class="flex items-center justify-center min-h-64">
    <div class="text-center text-gray-500">
        <p class="text-4xl mb-4">🚧</p>
        <p class="text-xl font-medium">Segera Hadir</p>
        <p class="text-sm mt-2">Fitur ini sedang dalam pengembangan</p>
    </div>
</div>
@endsection
```

---

## VERIFIKASI

```bash
# Buka browser → navbar harus menampilkan menu baru
# Mobile view (< 768px) → bottom tab bar tampil
# Semua link tidak 404 (pointing ke coming-soon atau route yang sudah ada)
# php artisan config:clear && php artisan cache:clear
```

Lanjutkan ke **[phase-2a-booking-invoice.md](phase-2a-booking-invoice.md)** dan **[phase-2b-ecommerce.md](phase-2b-ecommerce.md)**.
