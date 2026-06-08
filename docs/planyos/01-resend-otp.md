# Plan 01 — Verifikasi Email pakai OTP + Resend OTP

## 1. Tujuan (apa yang ingin dicapai)
Setelah user **mendaftar**, kita kirim **kode OTP 6 digit** ke email-nya. User memasukkan kode itu
di halaman verifikasi. Di halaman tersebut ada tombol **"Kirim ulang kode"** yang:
- hanya bisa ditekan lagi setelah **cooldown 60 detik** (ada timer hitung mundur),
- dibatasi **rate-limit** (maks beberapa kali per menit) supaya tidak di-spam,
- mengirim kode baru dan meng-_invalidate_ kode lama.

Kenapa OTP, bukan link verifikasi bawaan Fortify? Karena diminta eksplisit, dan OTP lebih cocok untuk
flow mobile / kirim-ulang. Kita **tidak menghapus** mekanisme Fortify, hanya menambah lapisan OTP.

## 2. Desain singkat (alur)
```
Register  ─►  CreateNewUser bikin user  ─►  event Registered
                                              │
                                              ▼
                              OtpService::send($user,'email_verification')
                                  - generate kode 6 digit
                                  - simpan ke tabel otp_codes (HASH, bukan plain)
                                  - kirim OtpCodeMail ke email
                                              │
   redirect ke /otp  (id_user disimpan di session 'otp:id_user')
                                              │
        User isi kode ─► POST /otp/verify ─► OtpService::verify()
                                  ├─ cocok & belum expired ─► email_verified_at = now(); hapus kode; lanjut
                                  └─ salah / expired ─► balik dengan error

        Tombol "Kirim ulang" ─► POST /otp/resend ─► (cek cooldown 60s + rate limit) ─► send ulang
```

## 3. Keputusan desain (biar tidak over-engineering & tidak merusak yang ada)
- OTP disimpan **di-hash** (`Hash::make`) di tabel terpisah `otp_codes`, bukan plain text. Aman bila DB bocor.
- Verifikasi **tidak dipaksakan global** (tidak mengaktifkan `MustVerifyEmail` di User) supaya
  akun seed/existing yang sudah aktif tidak ikut terkunci. Halaman OTP digate oleh **session flag**
  yang di-set saat registrasi. Disediakan juga middleware opsional `EnsureEmailVerified` yang
  **belum dipasang** ke route mana pun — tinggal dipakai kalau nanti mau dipaksakan.
- Kirim email **sinkron** (tanpa queue) — sesuai server `php artisan serve`.
- Purpose disimpan sebagai kolom (`email_verification`) supaya tabel OTP bisa dipakai ulang
  untuk keperluan lain di masa depan (mis. OTP reset password).

## 4. File yang dibuat / diubah

### 4.1 Migration — tabel `otp_codes` (BARU)
`database/migrations/2026_06_08_000001_create_otp_codes_table.php`
```php
Schema::create('otp_codes', function (Blueprint $table) {
    $table->id('id_otp');
    $table->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $table->string('purpose', 40)->default('email_verification'); // bisa dipakai ulang
    $table->string('code_hash');            // hash dari 6 digit, TIDAK menyimpan plain
    $table->unsignedTinyInteger('attempts')->default(0); // jumlah percobaan salah
    $table->timestamp('expires_at');
    $table->timestamp('consumed_at')->nullable();
    $table->timestamps();
    $table->index(['id_user', 'purpose']);
});
```

### 4.2 Model — `app/Models/OtpCode.php` (BARU)
- `$primaryKey = 'id_otp'`, `$fillable` untuk semua kolom kerja.
- cast `expires_at`,`consumed_at` ke datetime.
- helper `isExpired()`, `isConsumed()`, relasi `user()`.

### 4.3 Service — `app/Services/OtpService.php` (BARU) — **jantung fitur**
Konstanta:
- `EXPIRY_MINUTES = 10` (kode kadaluarsa 10 menit)
- `RESEND_COOLDOWN_SECONDS = 60`
- `MAX_ATTEMPTS = 5` (salah ketik kode maks 5x lalu kode hangus)

Method:
- `send(User $user, string $purpose): void`
  1. hapus/invalidate OTP `purpose` lama milik user (biar cuma 1 yang aktif),
  2. generate kode: `str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`,
  3. simpan row baru `code_hash = Hash::make($code)`, `expires_at = now()->addMinutes(10)`,
  4. `Mail::to($user->email)->send(new OtpCodeMail($user, $code))`.
- `verify(User $user, string $code, string $purpose): bool`
  - ambil OTP aktif terbaru (belum consumed, belum expired). Tidak ada → false.
  - kalau `attempts >= MAX_ATTEMPTS` → hangus, false.
  - `Hash::check($code, $otp->code_hash)` benar → set `consumed_at = now()`, return true.
  - salah → `increment('attempts')`, return false.
- `canResend(User $user, string $purpose): bool` dan `secondsUntilResend(...)` — cek row terbaru,
  bandingkan `created_at` + cooldown dengan `now()`. Dipakai untuk timer & guard.

### 4.4 Mailable — `app/Mail/OtpCodeMail.php` (BARU)
- subject "Kode Verifikasi VIYGO".
- view markdown/blade `emails.otp-code` menampilkan kode besar + masa berlaku 10 menit.

### 4.5 View email — `resources/views/emails/otp-code.blade.php` (BARU)
HTML email sederhana bertema VIYGO (copper di latar gelap), menampilkan `{{ $code }}`.

### 4.6 Controller — `app/Http/Controllers/Auth/OtpController.php` (BARU)
- `show()` — tampilkan `pages.auth.verify-otp`. Ambil user dari session `otp:id_user`
  (atau `auth()->user()` kalau sudah login). Kalau tidak ada → redirect login.
- `verify(Request $request)` — validasi `code` (6 digit). Panggil `OtpService::verify`.
  - sukses → set `email_verified_at`, hapus session flag, redirect ke home/ dashboard + flash sukses.
  - gagal → `back()->withErrors(['code' => 'Kode salah atau kedaluwarsa.'])`.
- `resend(Request $request)` — cek `OtpService::canResend`. Kalau belum boleh →
  `back()->withErrors(['resend' => 'Tunggu N detik lagi.'])`. Kalau boleh → `send()` + flash.

### 4.7 View halaman OTP — `resources/views/pages/auth/verify-otp.blade.php` (BARU)
- Input 6 kotak / 1 input `inputmode="numeric" maxlength="6"`.
- Tombol **Verifikasi**.
- Tombol/looplink **Kirim ulang kode** dengan **JS countdown** dari `secondsUntilResend`.
  Saat masih cooldown → tombol disabled + teks "Kirim ulang dalam 0:45".

### 4.8 Routes — `routes/web.php` (UBAH, tambah grup)
```php
Route::middleware('throttle:6,1')->group(function () {
    Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
});
```
`throttle:6,1` = maks 6 request/menit/IP (lapisan rate-limit ekstra di atas cooldown 60 detik).

### 4.9 Trigger kirim OTP saat register — `app/Listeners/SendOtpOnRegistration.php` (BARU)
Listener untuk event `Illuminate\Auth\Events\Registered`:
- `OtpService::send($event->user, 'email_verification')`
- simpan `session(['otp:id_user' => $event->user->id_user])`.
Daftarkan di `app/Providers/AppServiceProvider.php` (atau `EventServiceProvider`) via
`Event::listen(Registered::class, SendOtpOnRegistration::class)`.

> Catatan: Fortify men-dispatch `Registered` otomatis setelah user dibuat. Setelah register,
> Fortify default redirect ke home; untuk mengarahkan ke `/otp` kita override
> `RegisterResponse` **atau** lebih simpel: home mendeteksi session `otp:id_user` & banner
> "verifikasi email kamu". Demi keandalan, plan ini meng-_custom_ `RegisterResponse` agar
> redirect ke `route('otp.show')`.

### 4.10 Middleware opsional — `app/Http/Middleware/EnsureEmailVerified.php` (BARU, tidak dipasang)
Kalau `auth()->user()->email_verified_at === null` → redirect `otp.show`. Disediakan untuk
masa depan; **tidak** didaftarkan ke route mana pun sekarang agar tidak mengunci akun lama.

## 5. Edge cases & keamanan
- **Brute force kode**: dibatasi `MAX_ATTEMPTS=5` per kode + `throttle:6,1` per IP.
- **Spam resend**: cooldown 60 detik (server-side, bukan cuma JS) + throttle route.
- **Kode bocor di DB**: disimpan hash, bukan plain.
- **Expired**: 10 menit; verifikasi menolak yang lewat.
- **User refresh / kode lama**: setiap `send()` meng-invalidate kode sebelumnya.

## 6. Cara test manual
1. `php artisan migrate`.
2. Daftar akun baru di `/register`.
3. Buka `storage/logs/laravel.log` → cari email berisi 6 digit (karena `MAIL_MAILER=log`).
4. Masuk `/otp`, ketik kode → harus sukses & `email_verified_at` terisi.
5. Ketik kode salah 5x → kode hangus.
6. Klik "Kirim ulang" dua kali cepat → yang kedua ditolak (cooldown).

## 7. Definition of Done
- [ ] Migrasi tabel `otp_codes` jalan.
- [ ] Register memicu email OTP (terlihat di log).
- [ ] Halaman `/otp` verifikasi kode benar/salah dengan pesan jelas.
- [ ] Tombol resend dengan countdown + cooldown server-side bekerja.
- [ ] Tidak ada akun existing yang terkunci (verifikasi tidak dipaksakan global).
