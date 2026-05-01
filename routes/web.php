<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LookbookController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TreatmentFilesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes — VIYGO Beauty Marketplace
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cari', [SearchController::class, 'index'])->name('cari');
Route::get('/kategori/{slug}', [KategoriController::class, 'show'])->name('kategori.show');
Route::get('/salon/{slug}', [SalonController::class, 'show'])->name('salon.show');
Route::get('/gift-card', [GiftCardController::class, 'index'])->name('gift-card');
Route::get('/lookbook', [LookbookController::class, 'index'])->name('lookbook');
Route::get('/treatment-files', [TreatmentFilesController::class, 'index'])->name('treatment-files');
Route::get('/mitra', [MitraController::class, 'index'])->name('mitra');

/*
|--------------------------------------------------------------------------
| Auth-Protected Routes
|--------------------------------------------------------------------------
| Booking is open to any authenticated, verified user (customers and admins
| who want to test bookings). The `/akun` panel is restricted to customers.
| Salon owners use the Filament `/owner` panel; admins use `/admin`.
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Booking flow — any authenticated user
    Route::get('/salon/{slug}/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/salon/{slug}/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{kode}/konfirmasi', [BookingController::class, 'konfirmasi'])->name('booking.konfirmasi');
    Route::post('/booking/{kode}/batal', [BookingController::class, 'batal'])->name('booking.batal');

    // Customer-only account panel
    Route::middleware('role:customer')
        ->prefix('akun')
        ->name('akun.')
        ->group(function () {
            Route::get('/', [AkunController::class, 'index'])->name('index');
            Route::get('/bookings', [AkunController::class, 'bookings'])->name('bookings');
            Route::get('/favorit', [AkunController::class, 'favorit'])->name('favorit');
            Route::get('/pengaturan', [AkunController::class, 'pengaturan'])->name('pengaturan');
            Route::put('/pengaturan', [AkunController::class, 'updatePengaturan'])->name('pengaturan.update');
            Route::get('/reward', [AkunController::class, 'reward'])->name('reward');
        });

    // Existing Livewire Flux dashboard (any role)
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
