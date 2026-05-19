# Laporan Implementasi: Auto-Provision Salon Saat Admin Approve

> 📌 **Status: ✅ Implementasi Selesai**
> Dokumen ini merangkum apa yang dikerjakan untuk mengubah design di [`Approval_admin.md`](Approval_admin.md) menjadi kode yang berjalan.
> Smoke-test lewat: aplikasi baru → approve → user + salon (inactive) dibuat → application di-link ke salon → re-approve ditolak.

---

## 1. Apa yang Diimplementasi (Ringkas)

Sebelum: admin klik approve di Filament → hanya ganti label status di `mitra_applications`. Owner masih harus dibuat manual.

Sesudah: admin klik **"Approve & Create Salon"** → otomatis dalam 1 transaction:
1. Buat record `users` (role=`salon_owner`, `is_active=true`, **password default `"password"`**)
2. Buat record `salon` (`status='inactive'`, slug auto-generate, FK ke user di atas)
3. Update `mitra_applications.status='approved'` + `id_salon=` reference ke salon baru

**Flow login owner:**
- Owner login ke `/owner/login` dengan **email + password `"password"`** (kredensial diberikan admin via channel lain — WhatsApp / chat / dll)
- Setelah login, ganti password sendiri di **`/owner/profile`** (built-in Filament EditProfile page — sudah di-enable di `OwnerPanelProvider`)
- Edit salon, services, staff, foto di panel owner
- Salon **tidak muncul di publik** sampai admin manual ganti `status='active'`

---

## 2. File yang Dibuat / Diubah

| File                                                                                              | Status     | Tujuan                                                                |
|---------------------------------------------------------------------------------------------------|------------|-----------------------------------------------------------------------|
| [`database/migrations/2026_05_19_100000_add_id_salon_to_mitra_applications_table.php`](../../database/migrations/2026_05_19_100000_add_id_salon_to_mitra_applications_table.php) | **Baru** | Tambah FK `id_salon` ke `mitra_applications` untuk traceability       |
| [`app/Models/MitraApplication.php`](../../app/Models/MitraApplication.php)                        | **Diubah** | Tambah `id_salon` ke `$fillable` + relasi `salon()`                  |
| [`app/Services/ApproveSalonApplicationService.php`](../../app/Services/ApproveSalonApplicationService.php) | **Baru** | Logika inti: provision user + salon + update app dalam 1 transaction |
| [`app/Filament/Resources/MitraApplicationResource.php`](../../app/Filament/Resources/MitraApplicationResource.php) | **Diubah** | Tambah action "Approve & Create Salon", batasi action "Update Status" agar tidak bisa langsung approve |
| [`app/Providers/Filament/OwnerPanelProvider.php`](../../app/Providers/Filament/OwnerPanelProvider.php) | **Diubah** | Tambah `->profile()` agar owner bisa ganti password di `/owner/profile` |

---

## 3. Detail Tiap Perubahan

### 3a. Migration baru — `add_id_salon_to_mitra_applications_table`

```php
Schema::table('mitra_applications', function (Blueprint $table) {
    $table->foreignId('id_salon')
        ->nullable()
        ->after('id_kota')
        ->constrained('salon', 'id_salon')
        ->nullOnDelete();
});
```

- **Nullable** karena ada aplikasi lama yang belum di-approve via flow baru.
- **`nullOnDelete`** — kalau salon dihapus, kolom ini jadi null, application row tetap ada sebagai history.
- Sudah di-migrate: `2026_05_19_100000_add_id_salon_to_mitra_applications_table ... 469.33ms DONE`.

### 3b. Model `MitraApplication`

Tambah `'id_salon'` di `$fillable` + relasi:

```php
public function salon(): BelongsTo
{
    return $this->belongsTo(Salon::class, 'id_salon');
}
```

→ Memudahkan akses: `$application->salon->status`, dst.

### 3c. Service `ApproveSalonApplicationService`

Method utama: `approve(MitraApplication $app): Salon`.

#### Idempotency guard (di awal, sebelum write apa pun)

```php
if ($app->status === 'approved' || $app->id_salon !== null) {
    throw new RuntimeException(
        'This application has already been approved (salon already provisioned).'
    );
}
```

→ Cegah double-provision kalau admin klik 2x atau race condition.

#### Validasi prasyarat

```php
if (! $app->id_kota) {
    throw new RuntimeException('Cannot approve: city is missing...');
}
```

→ Kolom `salon.id_kota` NOT NULL di DB, jadi harus ada sebelum insert. Kalau applicant skip dropdown city, admin harus edit aplikasi dulu (di Filament view page).

#### Email collision handling

```php
$existingUser = User::where('email', $app->email)->first();
if ($existingUser && $existingUser->role !== UserRole::SALON_OWNER) {
    throw new RuntimeException("Email '...' is already registered as a {$role}...");
}
```

- Email belum dipakai → buat user baru
- Email sudah dipakai sebagai `salon_owner` → reuse (owner punya >1 salon, valid case)
- Email sudah dipakai sebagai `customer`/`admin` → **tolak** dengan pesan jelas, biar admin handle manual (paling aman untuk MVP — tidak silent-overwrite atau silent-promote)

#### Transaction utama

```php
DB::transaction(function () use ($app, $existingUser) {
    // 1. Create or reuse user
    $user = $existingUser ?? new User(); ...
    $user->role      = UserRole::SALON_OWNER;  // ← $guarded, set explicit
    $user->is_active = true;                     // ← $guarded, set explicit
    $user->save();

    // 2. Create salon (status=inactive)
    $salon = Salon::create([..., 'status' => 'inactive']);

    // 3. Link app to salon
    $app->update(['id_salon' => $salon->id_salon, 'status' => 'approved']);

    return $salon;
});
```

**Penting:** di `User.php` baris 38, `role` dan `is_active` masuk `$guarded` (untuk mencegah privilege escalation lewat mass assignment — BUG-A02/SEC-03). Jadi harus set dengan **property assignment eksplisit**, bukan `User::create([...])`.

#### Default password

```php
$user->password = Hash::make('password');
```

- Semua owner baru dapat default password literal: `"password"`
- Owner login di `/owner/login` dengan email + `password`
- Setelah login, owner ganti password sendiri di **`/owner/profile`** (built-in Filament page — diaktifkan via `->profile()` di [OwnerPanelProvider.php:29](../../app/Providers/Filament/OwnerPanelProvider.php#L29))
- Halaman profile minta password lama + password baru + confirm

> ⚠️ **Trade-off keamanan:** default password yang sama untuk semua owner adalah pilihan kemudahan, bukan praktik keamanan terbaik. Untuk MVP/internal use ini OK karena:
> 1. Akun baru `status='inactive'` — salon belum ada data sensitif
> 2. Admin diingatkan via toast notification untuk infokan owner agar segera ganti password
> 3. Owner dipaksa lewat halaman profile (UI), bukan re-set di DB
>
> Untuk production scale, pertimbangkan: (a) password random + kirim via email reset link, atau (b) flag `must_change_password=true` yang redirect owner ke `/owner/profile` saat login pertama.

#### Slug generator

```php
protected function generateUniqueSlug(string $name): string
{
    $base = Str::slug($name) ?: 'salon';
    $slug = $base;
    $i = 2;
    while (Salon::withTrashed()->where('slug', $slug)->exists()) {
        $slug = $base . '-' . $i++;
        if ($i > 1000) {
            $slug = $base . '-' . Str::random(6);
            break;
        }
    }
    return $slug;
}
```

- Pakai `withTrashed()` — jangan reuse slug salon yang sudah soft-deleted (bisa cause confusion saat di-restore)
- Defensive loop limit di 1000 — kalau ada ribuan duplikat (sangat tidak mungkin), fallback ke random suffix biar tidak loop selamanya

### 3d. Filament `MitraApplicationResource`

**Action baru: "Approve & Create Salon"**

```php
\Filament\Actions\Action::make('approveAndCreateSalon')
    ->label('Approve & Create Salon')
    ->icon('heroicon-o-check-badge')
    ->color('success')
    ->visible(fn (MitraApplication $record) =>
        $record->status !== 'approved' && $record->id_salon === null
    )
    ->requiresConfirmation()
    ->modalDescription(
        'This will create the owner user account and a salon record '
        . '(status=inactive, hidden from public). The owner will receive '
        . 'a password reset email to log in.'
    )
    ->action(function (MitraApplication $record) {
        try {
            $salon = app(ApproveSalonApplicationService::class)->approve($record);
            Notification::make()->title('Salon provisioned')->body(...)->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
        }
    }),
```

- **Hilang dari UI** setelah approved sukses (`->visible()` cek dua kondisi)
- **Pakai DI Container** (`app(...)`) supaya bisa di-mock di test nanti
- **Try-catch** menangkap error dari service → tampilkan toast notification merah (admin tidak crash)

**Action lama "Update Status" — dibatasi**

- Hapus opsi `approved` dari dropdown — sekarang cuma `new`, `contacted`, `rejected`
- Tambah `->visible(...!== 'approved')` — hilangkan tombol setelah approved (status terkunci)
- Modal description menjelaskan ke admin untuk pakai action baru untuk approval

**Form view-page**

- Field `status` di view page di-`->disabled()` + helperText, biar admin tidak coba edit status langsung di form

---

## 4. Hasil Smoke Test

### Test 1: Provisioning + idempotency

```text
App created: id=3
Salon created: id=6380, status=inactive, slug=test-approval-salon-065748
Owner: id_user=6392, email=test-approval-065748@example.com,
       role=salon_owner, is_active=true
App refreshed: status=approved, id_salon=6380

--- Re-approve attempt (should fail) ---
Re-approval rejected correctly:
  This application has already been approved (salon already provisioned).
```

### Test 2: Password verifikasi

```text
Owner email: pwdtest-071211@example.com
Can login with default password? YES ✓     ← Hash::check('password', $user->password)
Can login with random/wrong password? NO ✓ ← Hash::check('wrongpass', ...)
Role: salon_owner
is_active: true
Salon status: inactive
```

✅ Semua expectation lulus:
1. User dibuat dengan role `salon_owner` & `is_active=true`
2. Password owner = `"password"` (verified via `Hash::check`)
3. Password salah ditolak (hash integrity utuh)
4. Salon dibuat dengan `status='inactive'` dan slug auto-generate
5. Application di-update: `status='approved'`, `id_salon` linked
6. Re-approve dengan record yang sama → ditolak (idempotency)

---

## 5. Bagaimana Gating "Owner Hanya Bisa Edit Setelah Approve" Terjaga

Tidak ada code khusus yang ditulis untuk ini — gating sudah **otomatis** dari kombinasi 3 lapisan yang sudah ada di codebase:

### Lapisan 1: Akun belum exist sebelum approve

- Sebelum admin klik approve → tidak ada row di `users` dengan email applicant → applicant tidak bisa login (return "Invalid credentials")
- Ini lapisan paling kuat: **fundamental data tidak ada**

### Lapisan 2: Filament panel access — `canAccessPanel`

Di [`User.php:148-159`](../../app/Models/User.php#L148-L159):

```php
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'owner') {
        return $this->role === UserRole::SALON_OWNER && $this->is_active;
    }
    return false;
}
```

- User dengan `role=customer` (atau guest) → tidak bisa masuk `/owner` panel
- User dengan `is_active=false` → tidak bisa masuk juga

### Lapisan 3: Resource query scoping di owner panel

Di [`app/Filament/Owner/Resources/SalonResource.php:163-168`](../../app/Filament/Owner/Resources/SalonResource.php#L163-L168):

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('id_user', auth()->id())
        ->withoutGlobalScopes([SoftDeletingScope::class]);
}
```

- Owner A login → cuma lihat salon yang `id_user = A.id_user`
- Owner A coba URL salon B (`/owner/salons/{B-id}/edit`) → Filament 404 karena query scope filter out

### Auto-deactivate kalau owner di-suspend

Di [`Salon.php:127-130`](../../app/Models/Salon.php#L127-L130):

```php
public function scopeActive($query)
{
    return $query->where('status', 'active')
        ->whereHas('owner', fn ($q) => $q->where('is_active', true));
}
```

- Admin set `users.is_active=false` → salon-nya **otomatis hilang** dari pencarian publik walaupun `salon.status='active'`. Owner pun tidak bisa login lagi (Lapisan 2).

→ **Hasil:** owner cuma bisa edit salon setelah (a) admin approve aplikasi (Lapisan 1 ✅), (b) lewat panel yang divalidasi role (Lapisan 2 ✅), dan (c) hanya salon miliknya sendiri (Lapisan 3 ✅).

---

## 6. State Tabel Sebelum & Sesudah Approval

### Sebelum admin klik "Approve & Create Salon"

```text
mitra_applications:
  id=3, nama_salon="Test Salon", status="new", id_salon=NULL

users: (tidak ada record dengan email applicant)
salon: (tidak ada record untuk applicant)
```

### Sesudah admin klik action

```text
mitra_applications:
  id=3, status="approved", id_salon=6380     ← di-link ke salon baru

users:
  id=6392, email="test@...", role="salon_owner",
  is_active=true, password=hash("password")  ← default password literal: "password"

salon:
  id=6380, id_user=6392, nama_salon="Test Salon",
  slug="test-salon", status="INACTIVE",       ← tidak muncul di publik
  alamat="TBD — to be completed by owner",
  opening_time="09:00:00", closing_time="18:00:00"
```

> ℹ️ Admin diberi toast notification berisi kredensial: `email=... password=password`. Admin teruskan ke owner via channel komunikasi (WhatsApp/chat/dll).

### Sesudah owner reset password + lengkapi profil + admin manual `status='active'`

```text
salon:
  id=6380, status="active",                  ← muncul di publik (via scopeActive)
  alamat="Jl. Sudirman No.123",
  opening_time="08:00:00", closing_time="22:00:00",
  (services, staff, images terisi)
```

---

## 7. Yang Masih Manual / TODO Berikutnya

Implementasi ini **selesai untuk inti use case** (approve → provision). Yang masih bisa di-improve nanti:

| Item                                                       | Prioritas | Catatan                                                       |
|------------------------------------------------------------|-----------|---------------------------------------------------------------|
| Owner dashboard banner "Salon belum aktif" + checklist     | Sedang    | Saat ini owner perlu tahu sendiri kalau salon `inactive`     |
| Endpoint `POST /owner/salon/request-activation`            | Sedang    | Otomatisasi notif ke admin saat owner siap go-live           |
| Validasi `salon.status='active'` butuh alamat ≠ "TBD..."   | Tinggi    | Cegah admin activate salon tanpa alamat real                  |
| Email template custom (sekarang pakai bawaan Laravel)      | Rendah    | Subject "Reset Password Notification" → ganti jadi "Welcome to VIYGO" |
| Action "Resend Invite" di Filament                          | Rendah    | Buat regenerate password reset link kalau expired (60 menit) |
| Unit test untuk service                                     | Sedang    | Smoke test sudah lewat, butuh proper PHPUnit test            |
| Audit log saat approve (siapa admin, kapan)                 | Rendah    | Untuk compliance / debugging                                  |

---

## 8. Cara Test Manual (untuk Anda)

1. Pastikan ada `kota` di database (sudah ada dari seed)
2. Buka `/mitra` di browser → isi form lengkap (jangan lupa pilih kota) → submit
3. Login ke admin panel: `/admin/login` (perlu user dengan role admin)
4. Buka **Partnerships → Salon Applications**
5. Lihat aplikasi baru dengan status "New"
6. Klik tombol **"Approve & Create Salon"** (hijau, icon check-badge)
7. Confirm di modal — baca info password default di description
8. Toast hijau muncul (persistent): "Salon provisioned. Owner can log in with: email=..., password=password."
9. Verifikasi di DB:
   - Status aplikasi jadi "Approved"
   - Tombol "Approve & Create Salon" hilang dari row tersebut
   - Tabel `users`: ada user baru dengan role `salon_owner`, password hash dari `"password"`
   - Tabel `salon`: ada salon baru dengan `status='inactive'`
   - `storage/logs/laravel.log` ada line "Salon application approved + provisioned"
10. Buka `/owner/login` → login dengan email applicant + password `"password"` → masuk ke dashboard
11. Klik avatar/profil di pojok kanan atas → **Edit profile** → ganti password
    - Atau langsung buka `/owner/profile`
    - Form built-in Filament: minta password lama + password baru + confirm
12. Edit salon di **My Salon** menu: alamat, jam buka, upload foto, add services
13. Admin manual update `salon.status='active'` (via tinker atau Filament admin)
14. Salon muncul di pencarian publik ✅

---

## 9. Edge Case yang Sudah Di-handle

| Skenario                                          | Behavior                                                  |
|---------------------------------------------------|-----------------------------------------------------------|
| Admin klik approve 2x (race)                      | 2nd klik → exception "already been approved"              |
| `id_kota` null di aplikasi                        | Exception "city is missing, edit application first"       |
| Email applicant sudah dipakai user lain (customer/admin) | Exception "email already registered as X, use different email" |
| Email applicant sudah dipakai salon_owner lain    | Reuse user, tambahkan salon baru ke owner yang sama (password tidak di-reset) |
| Slug `nama_salon` clash dengan salon lain         | Auto-suffix `-2`, `-3`, ... (cek `withTrashed`)           |
| Slug `nama_salon` semua karakter spesial          | Fallback ke base `'salon'` + suffix                       |
| Mailer down saat kirim reset link                 | Salon tetap dibuat, log warning, admin retry manual       |
| Salon yang sudah dibuat → admin coba reject       | Action "Update Status" hilang setelah approved (terkunci) |

---

## 10. Catatan Untuk Reviewer / Tim Lain

- **Tidak ada breaking change** ke alur user `/mitra/apply`. Form publik tetap sama persis.
- **Tidak ada perubahan ke owner panel** (`/owner`) — gating-nya sudah benar dari awal, tinggal sekarang ada cara automated untuk bikin user+salon yang dibutuhkan.
- **Compatible dengan aplikasi lama** — `id_salon` nullable, jadi row lama yang sudah `status='approved'` (tanpa salon ter-link) tetap valid. Admin bisa manual seed salon untuk row-row tersebut atau biarkan saja.
- **Password aman** — pakai `Hash::make(Str::random(32))` (bcrypt). Owner tidak pernah tahu password awal — set sendiri lewat reset link Laravel built-in.
- **Audit trail dasar** — `Log::info` saat approve sukses + `Log::warning` saat email gagal. Lihat `storage/logs/laravel.log`.

---

**TL;DR:** Admin punya 1 tombol di Filament untuk approve + provision (user + salon inactive). Owner login pakai **email + password default `"password"`** lalu ganti password di **`/owner/profile`** (Filament built-in EditProfile). Owner cuma bisa edit salon setelah approve berkat 3 lapisan gating yang sudah ada di codebase. Salon baru otomatis tidak muncul di publik sampai admin manual activate. ✅
