# Plan 03 — Login / Daftar dengan Google (OAuth via Socialite)

## 1. Tujuan
User bisa **"Continue with Google"** di halaman login & register. Klik → diarahkan ke Google →
setelah izin, balik ke app dalam keadaan **sudah login**. Kalau email-nya belum terdaftar,
sistem **otomatis membuat akun** (tanpa password) dan menandai email sebagai terverifikasi.

## 2. Prasyarat (di luar kode)
- Package **`laravel/socialite`** (di-install via `composer require laravel/socialite`).
- **Google Cloud Console** → OAuth 2.0 Client ID (tipe Web application):
  - Authorized redirect URI: `http://127.0.0.1:8000/auth/google/callback` (dev),
    nanti tambahkan domain production.
  - Dapatkan **Client ID** & **Client Secret** → taruh di `.env`.

## 3. Alur
```
[Login page]  "Continue with Google"  ─►  GET /auth/google/redirect
                                              └─ Socialite::driver('google')->redirect()
                                                      │  (user pilih akun & setujui)
                                                      ▼
                              GET /auth/google/callback ?code=...
                                  └─ $g = Socialite::driver('google')->user()
                                       ├─ cari user by google_id → ada? login.
                                       ├─ belum? cari by email:
                                       │     ├─ ada (akun lokal) → tautkan google_id, login.
                                       │     └─ tidak ada → buat user baru (tanpa password,
                                       │                     email_verified_at = now()), login.
                                       ▼
                                  redirect ke home / dashboard
```

## 4. File yang dibuat / diubah

### 4.1 Install package
```
composer require laravel/socialite
```

### 4.2 Migration — tambah kolom ke `users` (BARU)
`database/migrations/2026_06_08_000002_add_google_auth_to_users_table.php`
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique()->after('email');
    $table->string('avatar')->nullable()->after('profile_url'); // foto dari Google
    $table->string('password')->nullable()->change();           // user Google tak punya password
});
```
> `->change()` butuh `doctrine/dbal` pada Laravel lama; di Laravel 12 sudah didukung native.
> Kalau `change()` bermasalah di SQLite/driver tertentu, alternatif: biarkan password diisi
> string acak hashed untuk akun Google. Plan utama: jadikan nullable.

### 4.3 Config — `config/services.php` (UBAH)
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

### 4.4 `.env` & `.env.example` (UBAH)
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### 4.5 User model (UBAH)
- Tambah `google_id`, `avatar` ke `$fillable`.
  (Catatan: `password` tetap aman; `role`/`is_active`/`id_user` tetap di `$guarded`.)

### 4.6 Controller — `app/Http/Controllers/Auth/GoogleAuthController.php` (BARU)
```php
public function redirect()
{
    return Socialite::driver('google')->redirect();
}

public function callback()
{
    try {
        $g = Socialite::driver('google')->user();
    } catch (\Throwable $e) {
        return redirect()->route('login')->withErrors(['email' => 'Gagal login dengan Google.']);
    }

    // 1) sudah pernah pakai Google
    $user = User::where('google_id', $g->getId())->first();

    // 2) atau email-nya sudah terdaftar lokal → tautkan
    if (! $user) {
        $user = User::where('email', $g->getEmail())->first();
    }

    if ($user) {
        $user->google_id = $user->google_id ?: $g->getId();
        $user->avatar    = $g->getAvatar();
        if (! $user->email_verified_at) { $user->email_verified_at = now(); }
        $user->save();
    } else {
        // 3) buat akun baru tanpa password
        $parts = preg_split('/\s+/', trim($g->getName() ?? $g->getNickname() ?? 'User'), 2);
        $user = new User();
        $user->first_name        = $parts[0] ?? 'User';
        $user->last_name         = $parts[1] ?? null;
        $user->email             = $g->getEmail();
        $user->google_id         = $g->getId();
        $user->avatar            = $g->getAvatar();
        $user->password          = null;        // login hanya via Google
        $user->email_verified_at = now();
        $user->save();                          // role default 'customer' dari migration
    }

    Auth::login($user, remember: true);
    return redirect()->intended(route('home'));
}
```

### 4.7 Routes — `routes/web.php` (UBAH)
```php
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
```

### 4.8 Tombol UI — login & register (UBAH)
`resources/views/pages/auth/login.blade.php` & `register.blade.php`:
tambah pemisah "atau" + tombol:
```blade
<a href="{{ route('google.redirect') }}"
   class="flex items-center justify-center gap-3 w-full py-2.5 rounded-full border border-white/15 ...">
   <img src="https://www.google.com/favicon.ico" class="w-4 h-4" alt="">
   <span>Continue with Google</span>
</a>
```
(Komponen `x-auth-google-button` opsional supaya tidak duplikat markup di 2 file.)

## 5. Edge cases & keamanan
- **Email Google sama dengan akun lokal** → ditautkan (bukan bikin duplikat). Aman karena
  Google sudah memverifikasi kepemilikan email.
- **User tanpa password** mencoba login form biasa → gagal (password null), arahkan pakai Google.
  (Opsional: tampilkan hint. Tidak wajib untuk DoD.)
- **CSRF/state** OAuth ditangani Socialite (state param) otomatis.
- **Akun nonaktif** (`is_active=false`) → middleware `EnsureUserIsActive` yang sudah ada tetap berlaku
  di panel; untuk web umum, bisa ditambah cek bila perlu (di luar scope inti).
- **Tanpa kredensial** (`GOOGLE_CLIENT_ID` kosong) → tombol tetap tampil tapi redirect akan error
  dari Google; untuk dev tanpa kredensial, fitur lain tidak terganggu.

## 6. Cara test manual
1. Isi `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET` di `.env` dari Google Cloud Console.
2. `php artisan migrate`.
3. Buka `/login`, klik **Continue with Google** → pilih akun → balik dalam keadaan login.
4. Cek tabel `users`: ada baris baru dengan `google_id` terisi & `email_verified_at` terisi.
5. Logout, login Google lagi dengan email sama → tidak bikin akun baru (dipakai ulang).

## 7. Definition of Done
- [ ] `laravel/socialite` terpasang.
- [ ] Migrasi `google_id`/`avatar`/password-nullable jalan.
- [ ] Route redirect & callback berfungsi; akun baru/penautan benar.
- [ ] Tombol Google tampil di login & register.
- [ ] Tanpa kredensial Google, aplikasi lain tetap normal (tidak crash).
