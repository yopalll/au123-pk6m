<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\LookbookController;
use App\Http\Controllers\TreatmentFilesController;
use App\Http\Controllers\MitraController;

/*
|--------------------------------------------------------------------------
| Public Routes — VIYGO Library Salon
|--------------------------------------------------------------------------
*/

// ── Beranda ────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');

// ── Pencarian ──────────────────────────────────────────────────────────────
Route::get('/cari', [SearchController::class, 'index'])->name('cari');

// ── Kategori (Treatwell-style "library") ───────────────────────────────────
Route::get('/kategori/{slug}', [KategoriController::class, 'show'])->name('kategori.show');

// ── Detail Salon ───────────────────────────────────────────────────────────
Route::get('/salon/{slug}', [SalonController::class, 'show'])->name('salon.show');

// ── Gift Card ──────────────────────────────────────────────────────────────
Route::get('/gift-card', [GiftCardController::class, 'index'])->name('gift-card');

// ── Lookbook ───────────────────────────────────────────────────────────────
Route::get('/lookbook', [LookbookController::class, 'index'])->name('lookbook');

// ── Treatment Files ────────────────────────────────────────────────────────
Route::get('/treatment-files', [TreatmentFilesController::class, 'index'])->name('treatment-files');

// ── Mitra / Partner ────────────────────────────────────────────────────────
Route::get('/mitra', [MitraController::class, 'index'])->name('mitra');

/*
|--------------------------------------------------------------------------
| Auth-Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // ── Booking ───────────────────────────────────────────────────────────
    Route::get('/salon/{slug}/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/salon/{slug}/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{kode}/konfirmasi', [BookingController::class, 'konfirmasi'])->name('booking.konfirmasi');
    Route::post('/booking/{kode}/batal', [BookingController::class, 'batal'])->name('booking.batal');

    // ── Akun ──────────────────────────────────────────────────────────────
    Route::prefix('akun')->name('akun.')->group(function () {
        Route::get('/', [AkunController::class, 'index'])->name('index');
        Route::get('/bookings', [AkunController::class, 'bookings'])->name('bookings');
        Route::get('/favorit', [AkunController::class, 'favorit'])->name('favorit');
        Route::get('/pengaturan', [AkunController::class, 'pengaturan'])->name('pengaturan');
        Route::put('/pengaturan', [AkunController::class, 'updatePengaturan'])->name('pengaturan.update');
        Route::get('/reward', [AkunController::class, 'reward'])->name('reward');
    });

});

/*
|--------------------------------------------------------------------------
| Dashboard (existing)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
