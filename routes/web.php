<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LookbookController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StaticController;
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
Route::post('/mitra/apply', [MitraController::class, 'apply'])->name('mitra.apply');

// ── Static / informational pages (footer links) ───────────────────────────
Route::get('/about',   [StaticController::class, 'about'])->name('static.about');
Route::get('/careers', [StaticController::class, 'careers'])->name('static.careers');
Route::get('/press',   [StaticController::class, 'press'])->name('static.press');
Route::get('/help',    [StaticController::class, 'help'])->name('static.help');
Route::get('/contact', [StaticController::class, 'contact'])->name('static.contact');
Route::get('/privacy', [StaticController::class, 'privacy'])->name('static.privacy');
Route::get('/terms',   [StaticController::class, 'terms'])->name('static.terms');
Route::get('/cookies', [StaticController::class, 'cookies'])->name('static.cookies');
Route::post('/contact', [StaticController::class, 'submitContact'])->name('static.contact.submit');
Route::post('/newsletter', [StaticController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

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
    Route::get('/salon/{slug}/booking/slots', [BookingController::class, 'getSlots'])->name('booking.slots');
    Route::post('/salon/{slug}/booking', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{kode}/konfirmasi', [BookingController::class, 'konfirmasi'])->name('booking.konfirmasi');
    Route::post('/booking/{kode}/batal', [BookingController::class, 'batal'])->name('booking.batal');

    // Payment (Midtrans Snap)
    Route::get('/booking/{kode}/payment', [PaymentController::class, 'show'])->name('booking.payment');
    Route::post('/booking/{kode}/payment/token', [PaymentController::class, 'createSnapToken'])->name('booking.payment.token');

    // Customer-only account panel
    Route::middleware('role:customer')
        ->prefix('akun')
        ->name('akun.')
        ->group(function () {
            Route::get('/', [AkunController::class, 'index'])->name('index');
            Route::get('/bookings', [AkunController::class, 'bookings'])->name('bookings');
            Route::get('/favorit', [AkunController::class, 'favorit'])->name('favorit');
            Route::post('/favorit/{salon:slug}', [AkunController::class, 'toggleFavorit'])->name('favorit.toggle');
            Route::get('/pengaturan', [AkunController::class, 'pengaturan'])->name('pengaturan');
            Route::put('/pengaturan', [AkunController::class, 'updatePengaturan'])->name('pengaturan.update');
            Route::get('/reward', [AkunController::class, 'reward'])->name('reward');

            // Review submission for completed orders
            Route::get('/bookings/{kode}/review', [ReviewController::class, 'create'])->name('review.create');
            Route::post('/bookings/{kode}/review', [ReviewController::class, 'store'])->name('review.store');
        });

    // Existing Livewire Flux dashboard (any role)
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Midtrans webhook (no auth — Midtrans posts here, signature-verified instead).
// CSRF exception lives in bootstrap/app.php.
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook'])
    ->name('midtrans.webhook');

require __DIR__.'/settings.php';
