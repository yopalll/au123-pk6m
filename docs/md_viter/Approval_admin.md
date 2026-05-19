# Laporan: Auto-Provision Salon Saat Admin Approve Aplikasi Mitra

> 📌 **Status: Belum Diimplementasi (Design / Proposal Document)**
> Dokumen ini mendesain feature **"Approve & Create Salon"** — saat admin klik approve di Filament admin panel, sistem otomatis bikin record di tabel `users` (owner) + tabel `salon` (default `status=inactive`), supaya admin tidak perlu manual seed dua tabel itu.
>
> Lihat juga: [`md_viter2.md`](md_viter2.md) untuk alur mitra application yang sudah ada.

### 🔒 Prinsip Utama: Owner Hanya Bisa Edit Salon Setelah Admin Approve

Ini **aturan inti** yang menjaga supaya tidak ada salon liar di database:

| Tahap aplikasi          | Akun owner | Record salon | Owner bisa login? | Owner bisa edit salon? | Salon muncul publik? |
|-------------------------|------------|--------------|-------------------|------------------------|----------------------|
| Submit form (`new`)     | ❌ belum ada | ❌ belum ada | ❌                | ❌                    | ❌                   |
| Admin review (`contacted`) | ❌ belum ada | ❌ belum ada | ❌                | ❌                    | ❌                   |
| Admin reject (`rejected`)  | ❌ tidak dibuat | ❌ tidak dibuat | ❌            | ❌                    | ❌                   |
| **Admin approve (`approved`)** | ✅ dibuat | ✅ dibuat (`inactive`) | ✅ via reset pwd | ✅ **mulai bisa edit** | ❌ masih `inactive` |
| Owner request activation     | ✅      | ✅ (`inactive`) | ✅              | ✅                    | ❌                   |
| Admin activate (`active`)    | ✅      | ✅ (`active`)   | ✅              | ✅                    | ✅                   |

**Mekanisme gating-nya teknis sederhana:**
1. **Sebelum approve** → akun `users` & record `salon` belum exist → tidak ada apa-apa untuk di-login atau di-edit.
2. **Setelah approve** → akun dibuat, salon dibuat (`inactive`). Email reset password dikirim. Owner set password → login → masuk `/owner/dashboard` → bisa edit salon yang dia miliki sendiri.
3. **Authorization di owner dashboard:** semua endpoint owner harus cek `auth()->user()->id_user === $salon->id_user` (atau via Policy). Detail di §11.

---

## 1. Konteks & Masalah Saat Ini

### Kondisi sekarang

- User submit form di `/mitra` → INSERT ke `mitra_applications` (status=`new`)
- Admin review di Filament admin panel
- Admin klik **"Update Status"** → ganti status ke `approved`

### Masalahnya

Saat ini status `approved` di `mitra_applications` **hanya menandai admin sudah review** — **TIDAK** otomatis bikin:
- Record di tabel `users` (akun owner untuk login ke dashboard owner)
- Record di tabel `salon` (data salon di sistem)

→ Admin harus manual:
1. Bikin user baru di tabel `users` dengan role `salon_owner`
2. Bikin salon baru di tabel `salon` dengan `id_user` dari user yang baru dibuat
3. Set password dan komunikasikan ke owner via channel lain (email manual, WhatsApp, dll)

Ini **error-prone** + slow + tidak scalable.

---

## 2. Solusi yang Diusulkan

### High-level flow

```
[Admin di Filament panel /admin/mitra-applications]
        │
        │ klik action "Approve & Create Salon"
        ▼
[ApproveAndCreateSalonAction handler]
        │
        ├──▶ DB::transaction:
        │     1. INSERT users        (role='salon_owner', is_active=true, password=random_hash)
        │     2. INSERT salon         (id_user=above, status='inactive', slug auto-gen)
        │     3. UPDATE mitra_applications (status='approved')
        │     4. log activity di audit log (opsional)
        │
        ├──▶ Email invite ke owner
        │     • "Welcome to VIYGO — set up your salon"
        │     • link reset password (token Laravel built-in)
        │     • link ke /owner/dashboard
        │
        └──▶ Notif Filament: "Salon X created (inactive). Owner invite emailed."

[Owner buka link reset password]
        │
        │ set password, login
        ▼
[Dashboard Owner /owner]
        │
        ├──▶ Wajib lengkapi: alamat, jam buka, foto, services, staff
        │
        └──▶ Submit "Request Activation"
                │
                ▼
[Admin verifikasi data + activate]
        │
        ▼
[salon.status = 'active'] → muncul di pencarian publik
```

---

## 3. Mapping Field: `mitra_applications` → `users` + `salon`

### 3a. Tabel `users` (baru)

| Kolom         | Sumber                                  | Catatan                                            |
|---------------|------------------------------------------|----------------------------------------------------|
| `first_name`  | split `mitra_applications.nama_pemilik` | ambil kata pertama                                |
| `last_name`   | split `mitra_applications.nama_pemilik` | ambil sisa kata (nullable)                        |
| `email`       | `mitra_applications.email`              | **harus unique** — handle collision (lihat §5a)   |
| `phone_number`| `mitra_applications.phone`              | langsung copy                                     |
| `password`    | `Hash::make(Str::random(32))`           | random — owner reset via email link               |
| `role`        | `'salon_owner'`                         | hardcoded                                         |
| `is_active`   | `true`                                  | account langsung aktif (biar bisa login)          |
| `email_verified_at` | `null`                            | verifikasi via reset password link               |

### 3b. Tabel `salon` (baru)

| Kolom         | Sumber                                   | Catatan                                                            |
|---------------|------------------------------------------|--------------------------------------------------------------------|
| `id_user`     | id dari user baru di §3a                 | FK owner                                                           |
| `id_kota`     | `mitra_applications.id_kota`             | bisa `null` kalau applicant skip dropdown city — handle (§5b)     |
| `nama_salon`  | `mitra_applications.nama_salon`          | langsung copy (max 150)                                            |
| `slug`        | `Str::slug(nama_salon)`                  | unique — auto suffix `-2`, `-3`, dst kalau collision               |
| `alamat`      | **placeholder**: `'TBD — to be filled'`  | required di DB, akan dilengkapi owner di dashboard                |
| `deskripsi`   | `mitra_applications.catatan` (atau null) | reuse catatan applicant sebagai deskripsi awal                    |
| `phone_number`| `mitra_applications.phone`               | sama dengan owner — owner bisa ganti nanti                        |
| `opening_time`| `'09:00:00'` (default)                   | required, owner override di dashboard                              |
| `closing_time`| `'18:00:00'` (default)                   | required, owner override di dashboard                              |
| `image_url`   | `null`                                   | owner upload nanti                                                 |
| `latitude`, `longitude`, `maps_url` | `null`             | nullable di migration, owner set nanti                            |
| `rating`, `total_review` | `0`                            | default migration                                                  |
| `status`      | **`'inactive'`**                         | ← **yang user minta**                                              |

### 3c. Tabel `mitra_applications` (update existing row)

| Kolom    | Diubah jadi   |
|----------|---------------|
| `status` | `'approved'`  |
| `updated_at` | otomatis  |

---

## 4. Perubahan Code yang Diperlukan

### 4a. **Tambah kolom `id_salon` di `mitra_applications`** (opsional tapi recommended)

Biar bisa trace "aplikasi mana → salon mana". Migration baru:

```php
// database/migrations/2026_XX_XX_add_id_salon_to_mitra_applications.php
Schema::table('mitra_applications', function (Blueprint $table) {
    $table->foreignId('id_salon')
        ->nullable()
        ->after('id_kota')
        ->constrained('salon', 'id_salon')
        ->nullOnDelete();
});
```

### 4b. **Service class baru: `ApproveSalonApplicationService`**

```php
// app/Services/ApproveSalonApplicationService.php
class ApproveSalonApplicationService
{
    public function approve(MitraApplication $app): Salon
    {
        return DB::transaction(function () use ($app) {
            // 1. Cek email collision
            $existingUser = User::where('email', $app->email)->first();

            $user = $existingUser ?? User::create([
                'first_name'   => Str::before($app->nama_pemilik, ' '),
                'last_name'    => Str::after($app->nama_pemilik, ' ') ?: null,
                'email'        => $app->email,
                'phone_number' => $app->phone,
                'password'     => Hash::make(Str::random(32)),
                'role'         => 'salon_owner',
                'is_active'    => true,
            ]);

            // 2. Buat salon — status inactive
            $salon = Salon::create([
                'id_user'      => $user->id_user,
                'id_kota'      => $app->id_kota,
                'nama_salon'   => $app->nama_salon,
                'slug'         => $this->generateUniqueSlug($app->nama_salon),
                'alamat'       => 'TBD — to be filled by owner',
                'deskripsi'    => $app->catatan,
                'phone_number' => $app->phone,
                'opening_time' => '09:00:00',
                'closing_time' => '18:00:00',
                'status'       => 'inactive',  // ← user request
            ]);

            // 3. Update aplikasi
            $app->update([
                'status'   => 'approved',
                'id_salon' => $salon->id_salon,  // kalau migration §4a sudah jalan
            ]);

            // 4. Kirim invite (di luar transaction biar gagal kirim email tidak rollback DB)
            return $salon;
        });
    }

    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (Salon::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
```

### 4c. **Tambah action di Filament resource**

Di [`MitraApplicationResource.php`](../../app/Filament/Resources/MitraApplicationResource.php), tambah action baru:

```php
\Filament\Actions\Action::make('approveAndCreateSalon')
    ->label('Approve & Create Salon')
    ->icon('heroicon-o-check-badge')
    ->color('success')
    ->visible(fn (MitraApplication $record) => $record->status !== 'approved')
    ->requiresConfirmation()
    ->modalHeading(fn ($record) => 'Approve: ' . $record->nama_salon)
    ->modalDescription('This will create the user + salon (inactive) and email an invite.')
    ->action(function (MitraApplication $record, ApproveSalonApplicationService $service) {
        $salon = $service->approve($record);

        // Trigger Laravel password reset link
        Password::sendResetLink(['email' => $record->email]);

        Notification::make()
            ->title('Salon created')
            ->body("Salon '{$salon->nama_salon}' (inactive). Owner invite emailed.")
            ->success()
            ->send();
    }),
```

### 4d. **Email invite template**

`resources/views/emails/owner-invite.blade.php`:
```blade
Hi {{ $ownerName }},

Welcome to VIYGO! Your salon application for "{{ $namaSalon }}" has been approved.

Your salon listing is currently INACTIVE. To go live:
1. Set your password: {{ $resetLink }}
2. Complete your salon profile (address, photos, working hours, services)
3. Request activation via the dashboard

If you didn't apply for this, ignore this email.

— VIYGO Partnerships
```

### 4e. **Activation flow (owner side)**

Di dashboard owner (`/owner`), tampilkan banner kalau `salon.status='inactive'`:

```blade
@if ($salon->status === 'inactive')
    <div class="bg-amber-50 border border-amber-200 p-4 rounded-lg">
        Your salon is not yet visible to customers. Please complete:
        @if ($salon->alamat === 'TBD — to be filled by owner') ❌ Address @endif
        @if (! $salon->services()->exists()) ❌ At least one service @endif
        @if (! $salon->images()->exists()) ❌ At least one photo @endif

        [Request Activation] (disabled until all ✅)
    </div>
@endif
```

Request activation → notif ke admin (atau auto-activate kalau semua field lengkap).

---

## 5. Edge Cases & Risiko

### 5a. Email sudah dipakai user lain

Skenario: applicant pakai email yang sudah terdaftar sebagai `customer`.

**Mitigasi:**
- **Opsi A (Recommended):** reuse user — promote role dari `customer` ke `salon_owner` (atau biarkan role tetap customer kalau model mendukung multi-role). Tapi role saat ini enum tunggal, jadi harus update role.
- **Opsi B:** tolak approval, kasih error ke admin: "Email ini sudah terdaftar. Gunakan email lain atau merge manual."
- **Opsi C:** buat user baru dengan suffix email `+salon@domain` — **jangan**, confusing.

→ Pilih **Opsi B** (paling aman & jelas) untuk MVP.

### 5b. `id_kota` null di mitra_applications

Migration `salon` butuh `id_kota` **NOT NULL** (`->constrained('kota')` tanpa nullable). Tapi `mitra_applications.id_kota` nullable.

**Mitigasi:**
- Cek di action: kalau `id_kota` null → tampilkan error, suruh admin minta data kota dulu (bisa input via modal sebelum confirm).
- Atau: ubah migration `salon` agar `id_kota` nullable (perlu migration baru — risk: kode existing yang assume not-null bisa pecah).

→ Pilih **input di modal** — admin diminta isi kota saat approve.

### 5c. Slug collision

Dua salon "Glow Studio" → slug pertama `glow-studio`, kedua `glow-studio-2`. Sudah di-handle di `generateUniqueSlug()` (§4b).

### 5d. Email gagal terkirim

Kalau `Password::sendResetLink` gagal (SMTP down, dll), salon **tetap dibuat** (status inactive). Admin bisa resend invite via action terpisah.

→ Salon record + log warning. Jangan rollback DB.

### 5e. Admin klik approve dua kali

**Mitigasi:** action `->visible()` di-disable kalau `status='approved'`. Tapi race condition tetap mungkin (admin buka 2 tab).

→ Tambah unique constraint atau check di service: kalau `$app->id_salon` sudah terisi, lempar exception "Already approved".

### 5f. Admin "Reject" salon yang sudah approved

Saat ini status enum: `new, contacted, approved, rejected`. Kalau dari `approved` admin ubah ke `rejected` — apakah salon yang sudah di-create di-delete?

→ Pilihan:
- **Soft delete salon + soft delete user** (salon sudah pakai `SoftDeletes`)
- **Atau:** larangan ubah status dari `approved` ke selain `approved`. Untuk batalin, admin harus deactivate salon secara manual.

→ Pilih **opsi kedua** (lebih aman) untuk MVP.

### 5g. User reset password link expired (default 60 menit di Laravel)

**Mitigasi:** kasih action "Resend Invite" di Filament untuk regenerate link.

---

## 6. Diagram Aliran Data

```
┌──────────────────────────────┐
│  Admin (Filament panel)      │
│  /admin/mitra-applications   │
└──────────────┬───────────────┘
               │ klik "Approve & Create Salon"
               │ (optional: input kota di modal kalau null)
               ▼
┌──────────────────────────────────────┐
│  ApproveSalonApplicationService      │
│  ─────────────────────────────────── │
│  DB::transaction {                   │
│    1. validate email tidak conflict  │
│    2. INSERT users ──────────────────┼──▶ users (role=salon_owner)
│    3. INSERT salon ──────────────────┼──▶ salon (status=INACTIVE)
│    4. UPDATE mitra_applications ─────┼──▶ status=approved, id_salon=...
│  }                                   │
│                                      │
│  5. Password::sendResetLink() ───────┼──▶ Email ke owner
│     (di luar transaction)            │      "Welcome — set password"
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│  Owner buka email → klik reset link  │
│  set password baru                   │
│  login ke /owner/dashboard           │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│  Dashboard owner                     │
│  • Banner "Salon belum aktif"        │
│  • Wajib lengkapi:                   │
│    - alamat                          │
│    - opening/closing time real       │
│    - upload foto                     │
│    - add minimal 1 service           │
│  • Tombol "Request Activation"       │
└──────────────┬───────────────────────┘
               │ semua lengkap → submit
               ▼
┌──────────────────────────────────────┐
│  Admin verifikasi → activate         │
│  UPDATE salon SET status='active'    │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│  Salon muncul di pencarian publik    │
│  (scopeActive di Salon.php memfilter │
│   status='active' AND owner.is_active│
│   = true)                            │
└──────────────────────────────────────┘
```

---

## 7. Tabel Database Sebelum & Sesudah

### Sebelum approval

| Tabel                 | Row                                           |
|-----------------------|-----------------------------------------------|
| `mitra_applications`  | 1 row, `status='new'`                         |
| `users`               | (tidak ada record untuk applicant)            |
| `salon`               | (tidak ada record untuk applicant)            |

### Sesudah approval

| Tabel                 | Row                                                              |
|-----------------------|------------------------------------------------------------------|
| `mitra_applications`  | row di-update: `status='approved'`, `id_salon=N`                |
| `users`               | 1 row baru: `role='salon_owner'`, `is_active=true`, pwd random  |
| `salon`               | 1 row baru: **`status='inactive'`**, FK ke user di atas         |

### Setelah owner lengkapi profil + admin activate

| Tabel                 | Row                                              |
|-----------------------|--------------------------------------------------|
| `salon`               | row di-update: `status='active'`, semua field lengkap |

---

## 8. Estimasi Effort

| Task                                              | Effort         |
|---------------------------------------------------|----------------|
| Migration tambah `id_salon` di mitra_applications | 15 menit       |
| Service `ApproveSalonApplicationService`          | 1 jam          |
| Filament action di MitraApplicationResource       | 30 menit       |
| Email template + tes pengiriman                   | 1 jam          |
| Owner dashboard banner + checklist completion     | 2-3 jam        |
| Owner activation request flow                     | 2 jam          |
| Unit test + edge case handling                    | 2 jam          |
| **Total**                                         | **~1 hari kerja** |

---

## 9. Checklist Implementasi (urutan kerja)

- [ ] Migration: `add_id_salon_to_mitra_applications`
- [ ] Update model `MitraApplication`: tambah `id_salon` di `$fillable` + relasi `salon()`
- [ ] Buat `app/Services/ApproveSalonApplicationService.php`
- [ ] Buat Mailable `OwnerInvite` atau pakai built-in `Password::sendResetLink`
- [ ] Update `MitraApplicationResource.php`: tambah action `approveAndCreateSalon`
- [ ] Update view dashboard owner: banner "Salon inactive" + completion checklist
- [ ] Endpoint `POST /owner/salon/request-activation`
- [ ] Filament: notif admin saat ada request activation
- [ ] Test: approval → email diterima → reset pwd → login → lengkapi → activate
- [ ] Test edge: email collision, kota null, double-approve, slug collision
- [ ] Dokumentasikan di internal handbook untuk tim ops

---

## 10. Catatan Penting

1. **Default `status='inactive'` (bukan `pending`)** sesuai request. Migration `salon` saat ini default `pending` — tapi kita explicit set `'inactive'` di service, jadi **tidak perlu** migration ubah default. Tinggal pastikan enum `salon.status` punya `'inactive'` (sudah ada: `['active', 'inactive', 'pending']`).

2. **`scopeActive` di [Salon.php:127-130](../../app/Models/Salon.php#L127-L130)** sudah memfilter `status='active'` + owner `is_active=true`. Salon dengan `status='inactive'` **otomatis tidak muncul di pencarian publik** — perfect untuk kasus ini.

3. **Trade-off password random vs invite token:** kita pakai `Hash::make(Str::random(32))` + `Password::sendResetLink` (built-in Laravel) — lebih sederhana daripada bikin invite token custom, dan udah secure by default.

4. **Alamat placeholder** `"TBD — to be filled by owner"` itu hack karena kolom `alamat` NOT NULL di migration. Cleaner: bikin migration ubah `alamat` jadi nullable. Tapi ini bisa pecah kode lain yang asumsi alamat selalu ada → harus audit dulu.

5. **Tidak ada perubahan ke alur `/mitra/apply`** — itu tetap sama. Yang berubah hanya sisi admin (approve action).

---

## 11. Detail Gating: "Owner Hanya Bisa Edit Setelah Approve"

Ini bagian terpenting — implementasinya **berlapis 3** supaya tidak ada celah:

### Lapisan 1: Akun belum exist sebelum approve

Sebelum admin klik approve:
- Tidak ada record di `users` dengan email applicant
- Tidak ada record di `salon` untuk applicant

→ Applicant **tidak punya cara login** ke sistem. Form login akan return "Invalid credentials" karena email tidak terdaftar. Ini adalah lapisan paling kuat — **mustahil dilewati** karena data fundamental-nya tidak ada.

### Lapisan 2: Route protection (middleware `auth` + role check)

Semua route owner harus minimal protected dengan:

```php
// routes/web.php
Route::middleware(['auth', 'role:salon_owner'])->prefix('owner')->group(function () {
    Route::get('/dashboard', [OwnerController::class, 'dashboard']);
    Route::get('/salon/edit', [OwnerSalonController::class, 'edit'])->name('owner.salon.edit');
    Route::post('/salon/update', [OwnerSalonController::class, 'update'])->name('owner.salon.update');
    Route::post('/salon/request-activation', [OwnerSalonController::class, 'requestActivation']);
    // ... staff, services, dll
});
```

→ User dengan `role=customer` (atau yang tidak login) **tidak bisa akses** halaman owner sama sekali, dapat 403/redirect.

### Lapisan 3: Authorization Policy (cek ownership)

Tambah `SalonPolicy` untuk pastikan owner cuma bisa edit salon miliknya sendiri (bukan salon orang lain):

```php
// app/Policies/SalonPolicy.php
class SalonPolicy
{
    public function update(User $user, Salon $salon): bool
    {
        return $user->id_user === $salon->id_user
            && $user->role === 'salon_owner'
            && $user->is_active === true;
    }

    public function requestActivation(User $user, Salon $salon): bool
    {
        return $this->update($user, $salon)
            && $salon->status === 'inactive';   // hanya inactive yang bisa minta activation
    }
}
```

Lalu di controller:

```php
// app/Http/Controllers/Owner/OwnerSalonController.php
public function update(Request $request)
{
    $salon = auth()->user()->salons()->firstOrFail();
    $this->authorize('update', $salon);   // ← gate

    $salon->update($request->validate([
        'alamat'       => 'required|string|max:500',
        'deskripsi'    => 'nullable|string|max:5000',
        'opening_time' => 'required|date_format:H:i',
        'closing_time' => 'required|date_format:H:i|after:opening_time',
        'phone_number' => 'nullable|string|max:20',
        // dll
    ]));

    return back()->with('success', 'Salon updated.');
}
```

### Apa yang terjadi kalau owner coba bypass?

| Skenario serangan                          | Hasil                                                    |
|--------------------------------------------|----------------------------------------------------------|
| Daftar di `/mitra` lalu langsung login     | ❌ — akun belum ada, login gagal                          |
| Tebak URL `/owner/salon/edit` tanpa login  | ❌ — middleware `auth` redirect ke login                  |
| Login sebagai customer biasa lalu akses    | ❌ — middleware `role:salon_owner` return 403             |
| Owner A coba edit salon owner B            | ❌ — `SalonPolicy@update` return false (ownership cek)    |
| Owner A coba `/owner/salon/edit?id=B`      | ❌ — controller pakai `auth()->user()->salons()->first()` |
| Owner edit salon-nya sebelum admin approve | ❌ — akun belum ada, tidak bisa login (lapisan 1)         |

### Apa yang owner BISA lakukan setelah approve?

Boleh edit (status `inactive` maupun `active`):
- ✅ `alamat`, `deskripsi`, `phone_number`
- ✅ `opening_time`, `closing_time`
- ✅ `latitude`, `longitude`, `maps_url`
- ✅ Upload `image` (via `salon_image` table)
- ✅ Manage `services` (CRUD)
- ✅ Manage `staff` (CRUD)
- ✅ Lihat & manage `orders` (yang masuk ke salon-nya)

**TIDAK boleh** diubah oleh owner (immutable / admin-only):
- ❌ `id_user` (ownership transfer harus via admin)
- ❌ `slug` (SEO-sensitive, admin only)
- ❌ `status` (active/inactive hanya admin)
- ❌ `rating`, `total_review` (di-update dari sistem review)
- ❌ `nama_salon` (opsional — bisa dibuat editable dengan log audit)

→ Atur lewat `$fillable` di model atau via whitelist di controller `validate()`.

### Auto-deactivate kalau owner non-aktif

Sudah ada di [Salon.php:127-130](../../app/Models/Salon.php#L127-L130):

```php
public function scopeActive($query)
{
    return $query->where('status', 'active')
        ->whereHas('owner', fn ($q) => $q->where('is_active', true));
}
```

→ Kalau admin set `users.is_active=false`, salon-nya **otomatis hilang** dari pencarian publik walaupun `salon.status='active'`. Owner pun tidak bisa login lagi.

---

## 12. Ringkasan Singkat (TL;DR)

1. **Sekarang:** admin approve cuma ganti label di `mitra_applications` — admin masih harus manual bikin user + salon.
2. **Setelah feature ini:** klik approve → otomatis bikin `users` (role=salon_owner) + `salon` (status=inactive) dalam 1 transaction + kirim email invite reset password.
3. **Gating "owner bisa edit setelah approve":** dijaga otomatis karena (a) akun belum exist sebelum approve, (b) middleware role check, (c) Policy ownership check.
4. **Salon `inactive`** otomatis tidak muncul di pencarian publik berkat `scopeActive` yang sudah ada.
5. **Untuk go-live:** owner lengkapi profil → submit "Request Activation" → admin verifikasi → set status ke `active`.
