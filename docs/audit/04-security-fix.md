# 04 — Security Fix Report

> Tanggal eksekusi: 2026-05-16
> Dikerjakan oleh: Fullstack Dev (AI Agent)
> Referensi audit: `04-security.md`

---

## Ringkasan

| ID      | Severity   | Judul                                                                | Status       |
|---------|-----------|----------------------------------------------------------------------|--------------|
| SEC-01  | 🔴 Critical | `.env` di working tree dengan secret Midtrans + APP_KEY             | ⚠️ Manual    |
| SEC-02  | 🔴 Critical | `APP_DEBUG=true` + `APP_URL` ngrok-public                           | ⚠️ Manual    |
| SEC-03  | 🟠 High    | `User::$fillable` memuat `role`, `is_active`                        | ✅ Fixed      |
| SEC-04  | 🟠 High    | Webhook tidak ada idempotency log (replay window)                    | ✅ Fixed      |
| SEC-05  | 🟠 High    | Tidak ada rate-limit pada `/midtrans/webhook`                        | ✅ Fixed      |
| SEC-06  | 🟠 High    | Route `booking.store` bisa menerima `id_service` array sangat besar | ✅ Fixed      |
| SEC-07  | 🟡 Medium  | Tidak ada CAPTCHA / honeypot di `/mitra/apply` & `/contact`         | ⚠️ Noted     |
| SEC-08  | 🟡 Medium  | Filament `canAccessPanel` cek role pakai string literal              | ✅ Fixed      |
| SEC-09  | 🟢 Low     | `Mail::raw` subject tidak escape CRLF dari user input               | ✅ Fixed      |

---

## Detail Pengerjaan

### ⚠️ SEC-01 — `.env` dengan kredensial production/sandbox
**Status**: Memerlukan tindakan manual segera. Ini adalah risiko kritis.

**Langkah wajib** (harus dilakukan oleh developer/owner):
1. **Login Midtrans Sandbox Dashboard** → Settings → Access Keys → Klik **Regenerate**.
2. Update `.env` lokal dengan key baru.
3. Jalankan:
   ```bash
   git rm --cached .env
   git commit -m "chore: remove tracked .env (security)"
   ```
4. Pastikan `.gitignore` mengandung `/.env`.
5. (Jika `.env` pernah ter-push ke history):
   ```bash
   git filter-repo --invert-paths --path .env
   # atau: bfg --delete-files .env
   git push --force-with-lease origin main
   ```
6. Generate `APP_KEY` baru: `php artisan key:generate`.

> ⚠️ **Credential yang harus di-rotate**: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `APP_KEY`.

---

### ⚠️ SEC-02 — `APP_DEBUG=true` + ngrok URL public
**Status**: Memerlukan tindakan manual. Stack-trace penuh bocor ke internet saat ngrok aktif.

**Tindakan**:
- Development lokal: `APP_DEBUG=true` BOLEH, tapi JANGAN kombinasikan dengan ngrok publik.
- Staging/production: Set `APP_DEBUG=false` di `.env`.
- Gunakan Sentry / Bugsnag untuk error tracking di staging.

---

### ✅ SEC-03 — `User::$fillable` memuat `role`, `is_active`
**File**: `app/Models/User.php`

**Risiko**: Setiap endpoint yang lupa menggunakan `$request->only(...)` membuka pintu privilege escalation. Customer bisa submit `role=admin` via form.

**Fix**: Dihapus dari `$fillable`, ditambah ke `$guarded`.
```php
protected $fillable = [
    'first_name', 'last_name', 'email', 'password', 'phone_number', 'profile_url',
];
protected $guarded = ['role', 'is_active', 'id_user'];
```

Assignment eksplisit tetap berfungsi: `$user->role = 'salon_owner'; $user->save();`

---

### ✅ SEC-04 — Webhook tidak ada idempotency / replay protection
**File**: `app/Http/Controllers/PaymentController.php`

**Masalah**: Midtrans retry webhook beberapa kali. Walau ada `lockForUpdate + in_array(status, [...]) return early`, tidak ada check berdasarkan `transaction_id` yang sudah final.

**Fix yang diimplementasikan**: Idempotency guard berdasarkan `transaction_id` + `status_pembayaran`.
```php
$incomingTxnId = (string) $notification->transaction_id;
if ($payment->id_transaksi === $incomingTxnId
    && $payment->status_pembayaran === 'completed') {
    Log::info('Midtrans webhook: duplicate notification, already processed', [...]);
    return;
}
```

**Benefit**: Mencegah double-processing pada Midtrans retry dan memproteksi dari theoretical replay attack pada transaksi yang sudah final.

---

### ✅ SEC-05 — Tidak ada rate-limit pada `/midtrans/webhook`
**File**: `routes/web.php`

**Masalah**: Endpoint webhook tidak ber-`throttle`. Attacker bisa flood, signature check tetap berjalan, CPU server terkuras.

**Fix**:
```php
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook'])
    ->middleware('throttle:120,1')  // 120 hit per menit — generous untuk Midtrans retry
    ->name('midtrans.webhook');
```

**Alasan 120/menit**: Midtrans mengirim retry agresif. Limit 120/menit cukup longgar untuk webhooks legitimate tapi membatasi DoS flood.

---

### ✅ SEC-06 — `id_service` array tanpa batas atas
**File**: `app/Http/Controllers/BookingController.php` (store & getSlots)

**Masalah**: `'id_service' => 'required|array|min:1'` tanpa `max:`. Attacker bisa kirim 10K id_service, tiap insert ke `order_detail`. DoS via tabel.

**Fix** (diterapkan pada kedua endpoint):
```php
// store()
'id_service'   => 'required|array|min:1|max:20',

// getSlots()
'service_ids'  => 'required|array|min:1|max:20',
```

---

### ⚠️ SEC-07 — Tidak ada CAPTCHA / honeypot di `/mitra/apply` & `/contact`
**Status**: Noted. Throttle (5/menit dan 10/menit) sudah ada, tapi spambot bisa tetap bypass.

**Implementasi yang disarankan** (belum dilakukan, perlu sprint frontend):
```html
<!-- Honeypot field - hide via CSS, bukan display:none (ada bot yang detect) -->
<input name="website" style="position:absolute;left:-9999px;opacity:0" tabindex="-1" autocomplete="off">
```
```php
// Validation
'website' => 'prohibited',
```

---

### ✅ SEC-08 — Filament `canAccessPanel` cek role pakai string literal
**File**: `app/Models/User.php` + **File baru**: `app/Constants/UserRole.php`

**Masalah**: Typo pada string `'salon_owner'` atau `'admin'` bisa bypass access control tanpa error.

**Fix**:
1. Dibuat class `app/Constants/UserRole.php` dengan constants `CUSTOMER`, `SALON_OWNER`, `ADMIN`.
2. `canAccessPanel()` diupdate untuk menggunakan constants:
```php
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'admin') {
        return $this->role === UserRole::ADMIN && $this->is_active;
    }
    if ($panel->getId() === 'owner') {
        return $this->role === UserRole::SALON_OWNER && $this->is_active;
    }
    return false;
}
```

---

### ✅ SEC-09 — `Mail::raw` subject CRLF injection
**File**: `app/Http/Controllers/MitraController.php`

**Masalah**: Email subject menggunakan `$application->nama_salon` mentah. Jika nama salon mengandung `\r\n`, attacker bisa inject email headers (CRLF injection).

**Fix**:
```php
$safeSubject = 'New VIYGO salon application: '
    . strip_tags(preg_replace('/[\r\n]+/', ' ', $application->nama_salon));
$msg->subject($safeSubject);
```

**Catatan**: Symfony Mailer biasanya sanitize ini, tapi defense-in-depth tetap penting.

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Models/User.php` | SEC-03, SEC-08: Remove fillable role/is_active; use UserRole constants |
| `app/Constants/UserRole.php` | **BARU** — SEC-08: UserRole constants class |
| `app/Http/Controllers/PaymentController.php` | SEC-04: Idempotency guard di webhook |
| `app/Http/Controllers/BookingController.php` | SEC-06: max:20 pada id_service & service_ids |
| `app/Http/Controllers/MitraController.php` | SEC-09: CRLF sanitization di email subject |
| `routes/web.php` | SEC-05: throttle:120,1 pada midtrans webhook |

---

## Tindakan Manual yang Masih Diperlukan

> ⚠️ **Wajib dilakukan sebelum go-live ke production:**

1. **SEC-01**: Rotate MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, APP_KEY. Remove `.env` dari Git tracking.
2. **SEC-02**: Set `APP_DEBUG=false` di staging/production. Jangan kombinasikan `APP_DEBUG=true` dengan ngrok publik.
3. **SEC-07**: Implementasikan honeypot/CAPTCHA di form `/mitra/apply` dan `/contact`.
