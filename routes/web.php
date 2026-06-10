<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EmptyReturnController;
use App\Http\Controllers\ExclusiveContentController;
use App\Http\Controllers\Forum\ForumController;
use App\Http\Controllers\Forum\ForumInteractionController;
use App\Http\Controllers\Forum\ForumReplyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LookbookController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\ProductCheckoutController;
use App\Http\Controllers\Shop\ProductOrderController;
use App\Http\Controllers\Shop\ProductPaymentController;
use App\Http\Controllers\Shop\ProductReviewController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\SkincarefinderController;
use App\Http\Controllers\Shop\WishlistController;
use App\Http\Controllers\StaticController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes — VIYGO Beauty Marketplace
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

// Landing page shown after a logged-in user is force-signed-out by the
// EnsureUserIsActive middleware because their is_active flag was flipped to false.
Route::view('/account-deactivated', 'pages::auth.account-deactivated')
    ->name('account.deactivated');
Route::get('/cari', [SearchController::class, 'index'])->name('cari');
Route::get('/kategori/{slug}', [KategoriController::class, 'show'])->name('kategori.show');
Route::get('/sub-kategori/{slug}', [KategoriController::class, 'showSub'])->name('sub-kategori.show');
Route::get('/salon/{slug}', [SalonController::class, 'show'])->name('salon.show');
Route::get('/mitra', [MitraController::class, 'index'])->name('mitra');
Route::post('/mitra/apply', [MitraController::class, 'apply'])
    ->middleware('throttle:5,1')
    ->name('mitra.apply');

// ── Google OAuth (Login / Daftar dengan Google) ───────────────────────────
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// ── Verifikasi Email via OTP ───────────────────────────────────────────────
// Dipakai setelah registrasi: user diarahkan ke /otp untuk memasukkan kode 6 digit.
// throttle:10,1 = lapisan rate-limit per IP di atas cooldown 60 detik di OtpService.
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
});

// ── Static / informational pages (footer links) ───────────────────────────
Route::get('/about', [StaticController::class, 'about'])->name('static.about');
Route::get('/careers', [StaticController::class, 'careers'])->name('static.careers');
Route::get('/press', [StaticController::class, 'press'])->name('static.press');
Route::get('/help', [StaticController::class, 'help'])->name('static.help');
Route::get('/contact', [StaticController::class, 'contact'])->name('static.contact');
Route::get('/privacy', [StaticController::class, 'privacy'])->name('static.privacy');
Route::get('/terms', [StaticController::class, 'terms'])->name('static.terms');
Route::get('/cookies', [StaticController::class, 'cookies'])->name('static.cookies');
Route::post('/contact', [StaticController::class, 'submitContact'])
    ->middleware('throttle:10,1')
    ->name('static.contact.submit');
Route::post('/newsletter', [StaticController::class, 'subscribeNewsletter'])
    ->middleware('throttle:3,1')
    ->name('newsletter.subscribe');

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
    Route::post('/promo/validate', [BookingController::class, 'validatePromo'])->name('promo.validate');
    Route::get('/booking/{kode}/konfirmasi', [BookingController::class, 'konfirmasi'])->name('booking.konfirmasi');
    Route::post('/booking/{kode}/batal', [BookingController::class, 'batal'])->name('booking.batal');

    // Payment (Midtrans Snap)
    Route::get('/booking/{kode}/payment', [PaymentController::class, 'show'])->name('booking.payment');
    Route::post('/booking/{kode}/payment/token', [PaymentController::class, 'createSnapToken'])->name('booking.payment.token');
    Route::post('/booking/{kode}/payment/finish', [PaymentController::class, 'finish'])->name('booking.payment.finish');

    // Customer-only account panel
    Route::middleware('role:customer')
        ->prefix('akun')
        ->name('akun.')
        ->group(function () {
            Route::get('/', [AkunController::class, 'index'])->name('index');
            Route::get('/bookings', [AkunController::class, 'bookings'])->name('bookings');
            // Modul 5 — Rincian Booking + Invoice PDF
            Route::get('/bookings/{kode}', [AkunController::class, 'bookingDetail'])->name('booking.detail');
            Route::get('/bookings/{kode}/invoice', [AkunController::class, 'downloadInvoice'])->name('booking.invoice');
            Route::get('/favorit', [AkunController::class, 'favorit'])->name('favorit');
            Route::post('/favorit/{salon}', [AkunController::class, 'toggleFavorit'])->name('favorit.toggle');
            Route::get('/pengaturan', [AkunController::class, 'pengaturan'])->name('pengaturan');
            Route::put('/pengaturan', [AkunController::class, 'updatePengaturan'])->name('pengaturan.update');
            Route::put('/pengaturan/password', [AkunController::class, 'updatePassword'])->name('pengaturan.password');

            // Review submission for completed orders
            Route::get('/bookings/{kode}/review', [ReviewController::class, 'create'])->name('review.create');
            Route::post('/bookings/{kode}/review', [ReviewController::class, 'store'])->name('review.store');
        });

    // Post-login landing — redirect berdasarkan role
    Route::get('dashboard', function () {
        $user = auth()->user();

        return match ($user->role) {
            'admin' => redirect('/admin'),
            'salon_owner' => redirect('/owner'),
            default => redirect()->route('akun.bookings'),
        };
    })->name('dashboard');
});

// Midtrans webhook (no auth — Midtrans posts here, signature-verified instead).
// CSRF exception lives in bootstrap/app.php.
// SEC-05: Rate-limit to 120 req/min (generous for Midtrans retries) to prevent DoS.
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('midtrans.webhook');

/*
|--------------------------------------------------------------------------
| Modul 1 — E-commerce Skincare (/shop)
|--------------------------------------------------------------------------
*/
Route::prefix('shop')->name('shop.')->group(function () {
    // Public
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/kategori/{slug}', [ShopController::class, 'kategori'])->name('kategori');
    Route::get('/koleksi/{slug}', [ShopController::class, 'koleksi'])->name('koleksi');
    Route::get('/cari', [ShopController::class, 'search'])->name('cari');
    Route::get('/skincare-finder', [SkincarefinderController::class, 'index'])->name('skincareFinder');
    Route::post('/skincare-finder', [SkincarefinderController::class, 'result'])->name('skincareFinder.result');
    Route::get('/wishlist/{user}/share', [WishlistController::class, 'share'])->name('wishlist.share');

    // Auth required
    Route::middleware('auth')->group(function () {
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

        Route::get('/cart', [CartController::class, 'index'])->name('cart');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
        Route::put('/cart/toggle', [CartController::class, 'toggle'])->name('cart.toggle');
        Route::put('/cart/select-all', [CartController::class, 'selectAll'])->name('cart.select-all');
        Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

        Route::post('/alamat', [ProductCheckoutController::class, 'storeAddress'])->name('address.store');
        Route::post('/buy-now', [ProductCheckoutController::class, 'buyNow'])->name('buynow');
        Route::get('/checkout', [ProductCheckoutController::class, 'index'])->name('checkout');
        Route::post('/checkout', [ProductCheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

        Route::get('/pesanan', [ProductOrderController::class, 'index'])->name('pesanan.index');
        Route::get('/order/{kode}', [ProductOrderController::class, 'show'])->name('order.show');
        Route::get('/order/{kode}/invoice', [ProductOrderController::class, 'invoice'])->name('order.invoice');

        Route::get('/order/{kode}/payment', [ProductPaymentController::class, 'index'])->name('order.payment');
        Route::post('/order/{kode}/payment/token', [ProductPaymentController::class, 'token'])->name('order.payment.token');
        Route::post('/order/{kode}/payment/finish', [ProductPaymentController::class, 'finish'])->name('order.payment.finish');

        Route::post('/produk/{slug}/review', [ProductReviewController::class, 'store'])->name('produk.review');
    });

    // Detail produk — paling akhir agar slug tidak menelan route lain di atas
    Route::get('/produk/{slug}', [ShopController::class, 'show'])->name('produk.show');
});

/*
|--------------------------------------------------------------------------
| Placeholder Routes V2 (SEMENTARA) — Lookbook, Komunitas, Empty Return
| Di-REPLACE saat Phase 3A/3B/3C dikerjakan.
|--------------------------------------------------------------------------
*/
$comingSoon = fn (string $title) => fn () => view('coming-soon', ['title' => $title]);

// Modul 2 — Lookbook
Route::get('/lookbook', [LookbookController::class, 'index'])->name('lookbook.index');
Route::get('/lookbook/{slug}', [LookbookController::class, 'show'])->name('lookbook.show');
Route::post('/lookbook/{slug}/shop-all', [LookbookController::class, 'shopAll'])->middleware('auth')->name('lookbook.shopAll');

// Modul 4 — Community Forum
Route::prefix('komunitas')->name('komunitas.')->group(function () {
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/leaderboard', [ForumController::class, 'leaderboard'])->name('leaderboard');

    Route::middleware('auth')->group(function () {
        Route::get('/thread/buat', [ForumController::class, 'create'])->name('thread.create');
        Route::post('/thread', [ForumController::class, 'store'])->middleware('throttle:10,1')->name('thread.store');
        Route::post('/thread/{thread:slug}/reply', [ForumReplyController::class, 'store'])->middleware('throttle:20,1')->name('reply.store');
        Route::post('/thread/{thread:slug}/like', [ForumInteractionController::class, 'likeThread'])->name('thread.like');
        Route::post('/reply/{reply}/like', [ForumInteractionController::class, 'likeReply'])->name('reply.like');
        Route::post('/thread/{thread:slug}/bookmark', [ForumInteractionController::class, 'bookmark'])->name('thread.bookmark');
    });

    Route::get('/thread/{thread:slug}', [ForumController::class, 'show'])->name('thread.show');
    Route::get('/{kategori:slug}', [ForumController::class, 'kategori'])->name('kategori');
});

// Modul 3 — Empty Return + Poin + Konten Eksklusif
Route::get('/empty-return', [EmptyReturnController::class, 'index'])->name('emptyReturn.index');
Route::middleware('auth')->group(function () {
    Route::get('/empty-return/submit', [EmptyReturnController::class, 'create'])->name('emptyReturn.create');
    Route::post('/empty-return/submit', [EmptyReturnController::class, 'store'])->middleware('throttle:5,1')->name('emptyReturn.store');
    Route::get('/empty-return/riwayat', [EmptyReturnController::class, 'history'])->name('emptyReturn.history');
    // Foto bukti empty-return di-stream lewat PHP (anti masalah symlink/Nginx di Docker).
    Route::get('/empty-return/photo/{photo}', [\App\Http\Controllers\EmptyReturnPhotoController::class, 'show'])->name('emptyReturn.photo');

    Route::get('/akun/poin', [PointController::class, 'index'])->name('akun.poin');
    Route::get('/akun/poin/riwayat', [PointController::class, 'history'])->name('akun.poin.history');

    Route::get('/eksklusif', [ExclusiveContentController::class, 'index'])->name('exclusive.index');
    Route::get('/eksklusif/{slug}', [ExclusiveContentController::class, 'show'])->name('exclusive.show');
});

Route::get('/akun/bookmarks', [ForumInteractionController::class, 'bookmarks'])->middleware('auth')->name('akun.bookmarks');

require __DIR__.'/settings.php';
