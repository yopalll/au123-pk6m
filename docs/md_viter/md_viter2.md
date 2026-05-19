# Alur Pendaftaran Salon (Mitra / "List your salon — Free")

Dokumen ini menjelaskan **alur lengkap** saat seorang pemilik salon mendaftarkan salonnya ke Viygo — mulai dari klik tombol **"List your salon — Free"** sampai aplikasi diapprove oleh admin.

> 📌 Penting: ini adalah alur **B2B** (pemilik salon mendaftarkan bisnis), berbeda dengan alur **B2C** booking customer yang ada di `md_viter.md`.

---

## Peta Singkat (Bird's-Eye View)

```
[1] Landing Mitra              GET  /mitra
        │
        ▼ klik "List your salon — Free"
[2] Scroll ke form #daftar     (anchor link, masih di halaman yang sama)
        │   • input nama_salon, nama_pemilik, email, phone
        │   • pilih kota (dropdown)
        │   • input catatan (opsional)
        │
        ▼ klik "Apply now — it's free →"
[3] Submit Application         POST /mitra/apply   (throttle: 5 req/menit)
        │   • validasi field
        │   • INSERT ke tabel `mitra_applications` (status=new)
        │   • Mail::raw → partnerships@viygo.com (best-effort)
        │
        ▼ redirect back + flash
[4] Konfirmasi di-Frontend     halaman yang sama, tampilkan "Application received"
        │
        ▼ (background, admin manual)
[5] Review di Admin Panel      Filament: /admin/mitra-applications
            • admin baca aplikasi
            • klik "Update Status" → contacted / approved / rejected
            • atau bulk action: Mark as Contacted / Rejected
```

---

## Detail Setiap Langkah

### [1] User Mendarat di Halaman Mitra

- **URL:** `GET /mitra`
- **Route:** [routes/web.php:31](../../routes/web.php#L31)
- **Controller:** [`MitraController@index`](../../app/Http/Controllers/MitraController.php#L13)
- **View:** [`resources/views/mitra/index.blade.php`](../../resources/views/mitra/index.blade.php)
- **Yang ditampilkan:**
  - Hero section: "Grow your salon faster with VIYGO"
  - Statistik: 8,750+ partner salons, 190K+ treatments, dsb.
  - Section "How it works" (3 step: Apply → Set up → Get bookings)
  - Benefits, Pricing, Testimonials, FAQ
  - **Form aplikasi** di section `#daftar` (bagian paling bawah)
- **Data yang dibaca dari DB:** tabel `kota` (untuk dropdown city di form).

> 🔗 Tombol "List your salon — Free" di hero adalah anchor link `href="#daftar"` — tidak navigate ke halaman lain, hanya scroll ke section form.

---

### [2] Form Aplikasi — Field yang Diisi User

Form di [`mitra/index.blade.php:225-293`](../../resources/views/mitra/index.blade.php#L225-L293) berisi:

| Field          | Tipe        | Wajib | Validasi                          |
|----------------|-------------|-------|-----------------------------------|
| `nama_salon`   | text        | ✅    | string, max 200                   |
| `nama_pemilik` | text        | ✅    | string, max 200                   |
| `email`        | email       | ✅    | format email, max 200             |
| `phone`        | tel         | ✅    | string, max 50                    |
| `kota`         | dropdown    | ❌    | integer, harus ada di `kota.id_kota` |
| `catatan`      | textarea    | ❌    | string, max 5000                  |

CSRF token di-include otomatis lewat `@csrf` Blade directive.

---

### [3] Submit Aplikasi — Data Masuk ke Database

- **URL:** `POST /mitra/apply`
- **Route:** [routes/web.php:32-34](../../routes/web.php#L32-L34)
- **Middleware:** `throttle:5,1` — **maksimal 5 submit per menit per IP** (anti-spam)
- **Controller:** [`MitraController@apply`](../../app/Http/Controllers/MitraController.php#L20)

#### 3a. Validasi Server-Side

```php
$data = $request->validate([
    'nama_salon'   => 'required|string|max:200',
    'nama_pemilik' => 'required|string|max:200',
    'email'        => 'required|email|max:200',
    'phone'        => 'required|string|max:50',
    'kota'         => 'nullable|integer|exists:kota,id_kota',
    'catatan'      => 'nullable|string|max:5000',
]);
```

Kalau validasi gagal → Laravel auto-redirect back dengan `$errors` & `old()` (ditampilkan di banner merah di atas form).

#### 3b. INSERT ke Tabel `mitra_applications`

Migration: [`2026_05_03_100000_create_mitra_applications_table.php`](../../database/migrations/2026_05_03_100000_create_mitra_applications_table.php)

| Kolom            | Diisi dengan                            |
|------------------|------------------------------------------|
| `id_application` | auto-increment                           |
| `nama_salon`     | dari form                                |
| `nama_pemilik`   | dari form                                |
| `email`          | dari form (di-index untuk pencarian)     |
| `phone`          | dari form                                |
| `id_kota`        | FK ke `kota` (nullable, `nullOnDelete`)  |
| `catatan`        | dari form (nullable, text panjang)       |
| `status`         | `'new'` (default, enum 4 nilai, di-index)|
| `created_at`     | otomatis                                 |
| `updated_at`     | otomatis                                 |

Enum `status`: `new` → `contacted` → `approved` / `rejected`.

> 💡 **Catatan keamanan**: form ini **tidak butuh auth** — siapa saja bisa submit. Anti-spam mengandalkan **throttle middleware** (`5,1` = 5 request per menit per IP). Tidak ada CAPTCHA.

#### 3c. Kirim Email Notifikasi ke Tim Partnerships

Setelah INSERT berhasil, controller kirim email plain-text:

- **Penerima:** `config('viygo.help_email', 'partners@viygo.com')`
- **Reply-To:** email pemilik salon (biar staff bisa langsung reply ke applicant)
- **Subject:** `"New VIYGO salon application: <nama_salon>"` — di-sanitasi dengan `strip_tags` + strip CRLF (**SEC-09**) untuk mencegah **email header injection**
- **Body:** plain-text berisi semua field dari form

Pengiriman email dibungkus `try/catch`:
- Kalau Mailer gagal (mis. SMTP misconfigured) → **TIDAK** menggagalkan submission user
- Cuma di-log sebagai warning: `Log::warning('mitra application mail failed', ...)`
- DB row tetap source of truth — notifikasi cuma best-effort

> 📧 Di environment **dev**, mailer default biasanya `log` driver → email "terkirim" cuma di-write ke `storage/logs/laravel.log`. Untuk testing, cek log untuk lihat isi email.

---

### [4] Konfirmasi Sukses di Frontend

Setelah submit sukses, controller return:

```php
return back()
    ->with('success', "Thanks {$nama_pemilik} — we'll review your application and get back to you within 24 hours.")
    ->with('mitra_applied', true);
```

Di view `mitra/index.blade.php:211-223`, kalau session `mitra_applied` ada → form **disembunyikan**, diganti panel hijau dengan checkmark:

> ✓ **Application received**
> Thanks [nama] — we'll review your application and get back to you within 24 hours.

User TIDAK login otomatis — masih anonymous. Tidak ada user account yang dibuat di step ini.

---

### [5] Review oleh Admin (Filament Admin Panel)

Aplikasi yang masuk dikelola admin lewat Filament admin panel:

- **URL:** `/admin/mitra-applications` (Filament admin)
- **Resource:** [`MitraApplicationResource`](../../app/Filament/Resources/MitraApplicationResource.php)
- **Navigation group:** "Partnerships"
- **Default sort:** `created_at desc` (terbaru di atas)

#### Yang Bisa Dilakukan Admin

1. **List view** — tabel berisi: ID, Salon, Owner, Email (copyable), Phone, City, Status (badge berwarna), Submitted date
2. **Filter** by status (new / contacted / approved / rejected)
3. **Search** by nama_salon, nama_pemilik, email, kota
4. **Update Status action** (row-level) — pilih status baru via modal, simpan
5. **Bulk actions:**
   - **Mark as Contacted** — set multiple rows ke `status=contacted`
   - **Mark as Rejected** — set multiple rows ke `status=rejected`
6. **View detail page** — semua field salon ditampilkan **disabled** (readonly), kecuali field `status` yang bisa di-edit

#### Workflow Status

```
   new (default saat submit)
        │
        ▼ admin call/email applicant
   contacted
        │
        ├──▶ approved   (admin onboard salon: buat user account, buat record `salon`)
        │
        └──▶ rejected   (tidak fit dengan platform)
```

> ⚠️ **Penting:** Status `approved` di tabel `mitra_applications` **belum** otomatis membuat record `salon` atau akun owner. Ini hanya menandai "sudah kami review & terima". Onboarding salon yang sesungguhnya (buat user + buat record `salon` + assign role owner) dilakukan **manual** oleh admin di luar resource ini.

---

## Ringkasan Tabel Database yang Terlibat

| Tabel                 | Kapan ditulis                          | Field penting                                  |
|-----------------------|----------------------------------------|------------------------------------------------|
| `mitra_applications`  | Step [3] — submit aplikasi             | `nama_salon`, `email`, `phone`, `status=new`  |
| `mitra_applications`  | Step [5] — admin update status         | `status` = `contacted` / `approved` / `rejected` |
| `kota`                | Step [1] (dibaca) — populate dropdown  | `id_kota`, `nama_kota`                         |

Tabel yang **tidak** ditulis di alur ini (penting untuk dipahami):
- `users` — owner account **belum** dibuat di step [3] atau [5]
- `salon` — record salon **belum** dibuat sampai admin manual onboard

---

## Diagram Aliran Data

```
┌──────────────────────┐
│  Pemilik Salon       │
│  (anonymous user)    │
└──────────┬───────────┘
           │ GET /mitra
           ▼
┌──────────────────────────────────┐
│  MitraController@index           │  ← baca tabel `kota`
│  view: mitra/index.blade.php     │
└──────────┬───────────────────────┘
           │ klik "List your salon"
           │ scroll ke #daftar
           │ isi form & submit
           │
           │ POST /mitra/apply (throttle 5/menit)
           ▼
┌──────────────────────────────────┐
│  MitraController@apply           │
│  ├─ validate()                   │
│  ├─ MitraApplication::create() ──┼──▶ INSERT mitra_applications
│  │                               │      (status='new')
│  ├─ Mail::raw() ─────────────────┼──▶ partners@viygo.com
│  │   (best-effort, try/catch)    │      (atau log/ di dev)
│  └─ return back() + flash         │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│  Frontend: panel sukses          │
│  "Application received ✓"        │
└──────────────────────────────────┘

                ⋮
                ⋮ (manual, beberapa jam/hari kemudian)
                ⋮

┌──────────────────────┐
│  Admin VIYGO         │
│  (Filament panel)    │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────────────┐
│  MitraApplicationResource        │
│  /admin/mitra-applications       │
│  ├─ List + filter + search       │
│  ├─ Update Status action ────────┼──▶ UPDATE mitra_applications
│  └─ Bulk actions                 │      SET status = ...
└──────────────────────────────────┘
```

---

## Catatan Keamanan

| Issue                              | Mitigasi                                                    |
|------------------------------------|-------------------------------------------------------------|
| Spam submission                    | `throttle:5,1` middleware (5 req/menit/IP)                  |
| CSRF                               | `@csrf` di form, di-handle Laravel otomatis                 |
| Email header injection             | `strip_tags` + strip CRLF di subject (SEC-09)               |
| Mail failure menggagalkan UX       | `try/catch` di Mail::raw, log warning, jangan throw         |
| SQL injection                      | Pakai Eloquent `create()` + mass assignment via `$fillable` |
| XSS via field text                 | Blade `{{ }}` auto-escape saat ditampilkan di admin panel   |
| Status enum tampering              | Kolom enum di DB membatasi nilai ke 4 yang valid            |
| Field validation                   | Server-side `validate()` di controller (jangan trust HTML5) |

---

## Apa yang **BELUM** Diimplementasi (Next Steps)

Berdasarkan code yang ada, beberapa hal yang **manual** dan idealnya bisa diotomatisasi:

1. **Auto-create user + salon saat status di-approve** — saat ini admin harus manual seed `users` + `salon` table setelah approve aplikasi.
2. **Email notifikasi balik ke applicant** — saat status berubah ke `contacted`/`approved`/`rejected`, applicant tidak otomatis dapat email.
3. **CAPTCHA / reCAPTCHA** — saat ini hanya throttle IP, bot bisa rotate IP.
4. **Self-onboarding portal** — ideally setelah approve, applicant dapat link untuk set password & lanjut self-onboarding (foto, services, jam buka, dll).
