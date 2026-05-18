# 04 — Security Audit

> Temuan dari sisi keamanan: secret-handling, auth boundary, mass-assignment, CSRF, debug, webhook integrity, dan supply-chain. Severity Critical/High harus ditutup sebelum public-launch.

---

## 🔴 SEC-01 — `.env` di working tree dengan secret Midtrans + APP_KEY asli

Lihat juga: BUG-A03.

Isi `.env` saat ini:
- `APP_KEY=base64:pK93LWlZbrjJ9HGwxMUDWMYvbATemY1O+pUdvSewbnw=`
- `MIDTRANS_SERVER_KEY=Mid-server-lc3MUQ0AvraQ8pvr9SONHWgF`
- `MIDTRANS_CLIENT_KEY=Mid-client-nvxeilk0GupI1tWd`
- `MIDTRANS_EXCHANGE_RATE=20500`
- `DB_PASSWORD=localpw`

### Aksi wajib
1. **Rotate** semua kredensial Midtrans (login dashboard → regenerate server-key). Sandbox sekalipun: bocor → orang lain bisa pakai kuota & test-fee.
2. **Re-generate APP_KEY** di tiap environment (`php artisan key:generate`).
3. Pastikan `.gitignore` punya:
   ```
   /.env
   /.env.*.local
   ```
4. Jika `.env` pernah di-commit ke histori:
   ```bash
   git filter-repo --invert-paths --path .env
   # atau pakai BFG: bfg --delete-files .env
   ```
   Lalu force-push (kabarkan tim dulu).
5. **Untuk produksi**: simpan secret di env-vars host (Laravel Forge / Vapor / Render) atau secret-manager.

---

## 🔴 SEC-02 — `APP_DEBUG=true` + `APP_URL` ngrok-public

Stack-trace dengan file path, SQL injection-class info, dan env values **bocor** ke pengakses internet. Bukti: screenshot `eror_v1/*` menampilkan path Windows + ngrok URL yang masih hidup.

### Aksi
- Local-dev: `APP_DEBUG=true` boleh, **jangan** kombinasikan dengan public ngrok.
- Staging: `APP_DEBUG=false`, pakai Sentry / Bugsnag untuk error tracking.

---

## 🟠 SEC-03 — `User::$fillable` memuat `role`, `is_active`

Lihat BUG-A02. Risk = privilege escalation jika ada endpoint baru mass-assign tanpa filter.

---

## 🟠 SEC-04 — Webhook Midtrans rely on `signature_key` tapi belt-and-braces, masih ada window untuk replay

**File**: [`app/Http/Controllers/PaymentController.php:257-373`](../app/Http/Controllers/PaymentController.php#L257)

Kode sudah benar (`hash_equals(expected, signature)`), bagus. Tapi:
- Tidak ada **idempotency log** — bila Midtrans retry kirim webhook beberapa kali (mereka memang retry), aktualnya kita re-execute (di-protect oleh `lockForUpdate + in_array(status, [...]) return early`). OK.
- Tidak ada validasi `transaction_time` — replay 30 hari kemudian (theoretical) bisa lolos.

### Aksi
1. Simpan `notification.transaction_id` di `pembayaran.id_transaksi` dan tolak notifikasi dengan `transaction_id` yang sudah final-state:
   ```php
   if ($payment->id_transaksi === $notification->transaction_id
       && $payment->status_pembayaran === 'completed') {
       return response()->json(['message' => 'duplicate, already processed'], 200);
   }
   ```
2. Validasi `notification.transaction_time` < now + tolerance, > 7 hari abaikan.

---

## 🟠 SEC-05 — Tidak ada rate-limit pada `/midtrans/webhook`

Endpoint webhook tidak ber-`throttle`. Attacker bisa flood, dan walau signature filter, server CPU tetap habis.

### Aksi
```php
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook'])
    ->middleware('throttle:120,1')   // 120 hit per menit, generous untuk Midtrans
    ->name('midtrans.webhook');
```

---

## 🟠 SEC-06 — Route `booking.store` dapat menerima `id_service` array sangat besar

**File**: [`app/Http/Controllers/BookingController.php:84-91`](../app/Http/Controllers/BookingController.php#L84)

Validasi `id_service => 'required|array|min:1'` tanpa `max:`. Attacker bisa kirim 10K id_service, tiap insert ke `order_detail`. DoS lewat tabel.

### Aksi
```php
'id_service'   => 'required|array|min:1|max:20',
'id_service.*' => 'integer|exists:service,id_service',
```
Sama untuk `getSlots` validation.

---

## 🟡 SEC-07 — Tidak ada CAPTCHA / honeypot di `/mitra/apply` & `/contact`

Throttle ada (5/menit, 10/menit), tapi tetap spamable. Lihat juga MitraController & StaticController.

### Aksi
Tambahkan honeypot field `<input name="website" style="display:none">` lalu validasi `'website' => 'prohibited'`. Atau pakai hCaptcha/Turnstile.

---

## 🟡 SEC-08 — Filament `canAccessPanel` cek role pakai string literal

**File**: [`app/Models/User.php:145-156`](../app/Models/User.php#L145)

Aman, tapi rapuh — typo `'salon_owner'` → bypass. Buat constant:
```php
// app/Constants/UserRole.php
class UserRole {
    public const CUSTOMER    = 'customer';
    public const SALON_OWNER = 'salon_owner';
    public const ADMIN       = 'admin';
}
```
Pakai di middleware, model, dan migration enum.

---

## 🟢 SEC-09 — Mail::raw di MitraController & StaticController tidak escape user input

**File**: `MitraController.php:44-57`, `StaticController.php:41-51`

Body email plain-text concatenate user-supplied data. Aman terhadap XSS karena plain-text, tapi:
- Email "Subject" pakai `$application->nama_salon` mentah → potentially CRLF injection (`\n` di nama salon → header injection). Mailer Symfony biasanya sanitasi, namun:

### Aksi
```php
->subject('New VIYGO salon application: ' . strip_tags(preg_replace('/[\r\n]+/', ' ', $application->nama_salon)));
```
