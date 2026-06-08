# Plan YOS — 3 Fitur Tambahan VIYGO

Folder ini berisi rencana detail (blueprint) untuk 3 fitur baru. Setiap file dibuat
**selengkap & sedetail mungkin** supaya bisa dibaca ulang, di-review, atau dikerjakan ulang
oleh siapa pun (termasuk AI agent) tanpa perlu menebak.

| # | Fitur | File | Inti masalah yang diselesaikan |
|---|-------|------|--------------------------------|
| 1 | **Resend OTP** | [01-resend-otp.md](01-resend-otp.md) | Verifikasi email pakai kode OTP 6 digit + tombol "Kirim ulang" dengan cooldown 60 detik & rate-limit |
| 2 | **Email Invoice** | [02-email-invoice.md](02-email-invoice.md) | Kirim invoice PDF otomatis ke email customer saat pembayaran sukses + tombol kirim ulang |
| 3 | **Google Auth** | [03-google-auth.md](03-google-auth.md) | Login / daftar pakai akun Google (OAuth via Laravel Socialite) |

## Urutan pengerjaan yang disarankan
1. **Resend OTP** — paling banyak file baru (model, service, mail, controller, view), tapi mandiri.
2. **Email Invoice** — menumpang infrastruktur Mail yang sama, jadi enak dikerjakan setelah OTP.
3. **Google Auth** — butuh package eksternal (`laravel/socialite`) + kredensial Google Cloud.

## Konteks teknis penting (kondisi codebase saat plan ditulis)
- **Auth**: Laravel **Fortify** (bukan Breeze). View auth di `resources/views/pages/auth/*`.
  Registrasi lewat `App\Actions\Fortify\CreateNewUser`.
- **User model**: primary key custom `id_user`. Kolom `role`,`is_active`,`id_user` ada di `$guarded`
  (anti privilege-escalation) — jangan mass-assign; set eksplisit lalu `save()`.
- **Mail**: `.env` `MAIL_MAILER=log` → semua email "terkirim" masuk ke `storage/logs/laravel.log`
  (aman buat development, tidak benar-benar mengirim).
- **Invoice PDF**: sudah ada `barryvdh/laravel-dompdf` + view `resources/views/pdf/invoice-produk.blade.php`
  & `invoice-booking.blade.php`. Selama ini hanya bisa **di-download**, belum dikirim email.
- **Payment sukses (shop)**: di-handle `App\Http\Controllers\Shop\ProductPaymentController@finish`
  (status `settlement`/`capture` → `order->status = paid`).
- **Server**: dijalankan dengan `php artisan serve` saja (tanpa queue worker), jadi semua email
  dikirim **sinkron** (tidak pakai queue) supaya tidak butuh `queue:work`.
