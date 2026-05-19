# Laporan Implementasi: Owner Bisa Buat Diskon untuk Salonnya

> 📌 **Status: ✅ Implementasi Selesai**
> Owner sekarang bisa CRUD discount/promo code untuk salon-nya sendiri lewat Filament Owner panel di menu **"Operations → Discounts"**. Promo yang dibuat owner hanya valid saat customer booking di salon yang punya promo tersebut.
>
> Lihat juga: [`tambahan_kat.md`](tambahan_kat.md), [`Approval_Admin2.md`](Approval_Admin2.md) untuk feature owner panel lainnya.

---

## 1. Apa yang Diimplementasi (Ringkas)

**Sebelum:**
- Tabel `promo` tidak punya kolom `id_salon` → semua promo platform-wide (admin only)
- Owner tidak punya cara bikin diskon untuk salon-nya

**Sesudah:**
- Tabel `promo` punya kolom `id_salon` nullable:
  - `NULL` → platform-wide promo (admin-managed, existing behavior)
  - `set N` → salon-specific promo, hanya valid saat booking di salon N
- Owner punya menu **"Discounts"** di Filament owner panel — CRUD diskon untuk salon-nya sendiri
- `BookingController::validatePromo` & `store` enforce salon match — promo salon A ditolak saat customer book salon B
- Admin panel `PromoResource` juga di-update: bisa pilih salon (opsional) saat create promo

---

## 2. File yang Dibuat / Diubah

| File | Status | Tujuan |
|------|--------|--------|
| [`database/migrations/2026_05_19_120000_add_id_salon_to_promo_table.php`](../../database/migrations/2026_05_19_120000_add_id_salon_to_promo_table.php) | **Baru** | Tambah FK `id_salon` nullable di tabel `promo` |
| [`app/Models/Promo.php`](../../app/Models/Promo.php) | **Diubah** | Tambah `id_salon` ke `$fillable` + relasi `salon()` |
| [`app/Models/Salon.php`](../../app/Models/Salon.php) | **Diubah** | Tambah relasi `promos()` HasMany |
| [`app/Http/Controllers/BookingController.php`](../../app/Http/Controllers/BookingController.php) | **Diubah** | `validatePromo` + `store`: tolak promo kalau salon-nya beda |
| [`resources/views/booking/create.blade.php`](../../resources/views/booking/create.blade.php) | **Diubah** | JS payload AJAX kirim `id_salon` ke `/promo/validate` |
| [`app/Filament/Owner/Resources/PromoResource.php`](../../app/Filament/Owner/Resources/PromoResource.php) | **Baru** | Resource untuk owner CRUD promo, scoped by `id_user` |
| [`app/Filament/Owner/Resources/PromoResource/Pages/ListPromos.php`](../../app/Filament/Owner/Resources/PromoResource/Pages/ListPromos.php) | **Baru** | List page |
| [`app/Filament/Owner/Resources/PromoResource/Pages/CreatePromo.php`](../../app/Filament/Owner/Resources/PromoResource/Pages/CreatePromo.php) | **Baru** | Create page + defense-in-depth ownership check |
| [`app/Filament/Owner/Resources/PromoResource/Pages/EditPromo.php`](../../app/Filament/Owner/Resources/PromoResource/Pages/EditPromo.php) | **Baru** | Edit page + defense-in-depth ownership check |
| [`app/Filament/Resources/PromoResource.php`](../../app/Filament/Resources/PromoResource.php) | **Diubah** | Tambah field `id_salon` di admin form + kolom "Salon" di tabel |

---

## 3. Detail Tiap Perubahan

### 3a. Migration: `add_id_salon_to_promo_table`

```php
Schema::table('promo', function (Blueprint $table) {
    $table->foreignId('id_salon')
        ->nullable()
        ->after('id_promo')
        ->constrained('salon', 'id_salon')
        ->cascadeOnDelete();
    $table->index('id_salon');
});
```

- **Nullable** karena promo platform-wide (admin) tetap exist tanpa salon
- **`cascadeOnDelete`** — kalau salon di-delete, promo-nya ikut hilang (mencegah orphan)
- **Index** pada `id_salon` untuk speedup query "promos belonging to salon X"

✅ Migration jalan: `2026_05_19_120000_add_id_salon_to_promo_table ... 255.34ms DONE`

### 3b. Model `Promo`

```php
public function salon(): BelongsTo
{
    return $this->belongsTo(Salon::class, 'id_salon');
}
```

Plus `'id_salon'` ditambah di `$fillable`. Relasi nullable — `$promo->salon` return null untuk platform-wide promos.

### 3c. Model `Salon`

```php
public function promos(): HasMany
{
    return $this->hasMany(Promo::class, 'id_salon');
}
```

→ `$salon->promos` return semua promo milik salon ini (tidak termasuk platform-wide).

### 3d. `BookingController::validatePromo`

Tambah validasi field `id_salon` + cek match:

```php
$data = $request->validate([
    'kode_promo' => 'required|string|max:50',
    'total'      => 'required|numeric|min:0',
    'id_salon'   => 'nullable|integer|exists:salon,id_salon',  // ← baru
]);

$promo = Promo::byCode($data['kode_promo'])->first();

if ($promo->id_salon && (int) ($data['id_salon'] ?? 0) !== (int) $promo->id_salon) {
    return response()->json([
        'valid'   => false,
        'message' => 'This promo code is only valid at a different salon.',
    ]);
}
```

→ Kalau promo punya `id_salon`, validasi bahwa customer book salon yang sama. Kalau promo platform-wide (`id_salon=null`), validasi di-skip (existing behavior).

### 3e. `BookingController::store`

Sama, tapi di submission booking (full request, bukan AJAX validate):

```php
if ($promo->id_salon && (int) $promo->id_salon !== (int) $salon->id_salon) {
    return back()->withInput()->withErrors([
        'kode_promo' => 'This promo code is only valid at a different salon.',
    ]);
}
```

→ Defense-in-depth: kalau user bypass JS validate-promo dan langsung submit, tetap ditolak server-side.

### 3f. Frontend `booking/create.blade.php`

Update JS payload AJAX:

```js
body: JSON.stringify({
    kode_promo: code,
    total: this.totalPrice,
    id_salon: @json($salon->id_salon)  // ← baru
}),
```

→ AJAX validate-promo sekarang include `id_salon` dari konteks halaman booking.

### 3g. Owner `PromoResource`

Resource baru di `app/Filament/Owner/Resources/PromoResource.php` dengan struktur form 4 section:

| Section | Field |
|---------|-------|
| **Promo Info** | `id_salon` (dropdown salon owner), `nama_promo`, `kode_promo` (auto-uppercase), `deskripsi_promo` |
| **Discount Amount** | `tipe_promo` (Percentage/Fixed), `diskon`, `diskon_max` (cap, kalau %), `min_transaksi` |
| **Availability** | `time_start`, `time_expired`, `stock`, `status` |
| **Usage Stats** | `used_counter` (read-only, otomatis ter-update sistem) |

**Highlights UX:**
- `tipe_promo` pakai `live()` → label `diskon` reactive (jadi `Discount (%)` atau `Discount (£)`)
- `diskon_max` hanya muncul kalau `tipe_promo='percentage'` (sembunyikan field yang irrelevant)
- `kode_promo` otomatis di-UPPERCASE saat save (`dehydrateStateUsing`) supaya konsisten dengan `Promo::byCode()` di backend
- `time_expired` validasi `after('time_start')` — tidak boleh expired sebelum start
- `used_counter` disabled — owner cuma lihat, tidak edit

**Authorization (3 lapisan):**

```php
// 1. Filament panel access — sudah di-handle User::canAccessPanel
// 2. Resource-level query scope
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->whereHas('salon', fn (Builder $q) => $q->where('id_user', auth()->id()))
        ->withoutGlobalScopes([SoftDeletingScope::class]);
}

// 3. Dropdown salon di form HANYA list salon milik user
Forms\Components\Select::make('id_salon')
    ->options(fn () => Salon::query()
        ->where('id_user', auth()->id())
        ->pluck('nama_salon', 'id_salon'))

// 4. Defense-in-depth: validate at submit (mutateFormDataBeforeCreate/Save)
if (! in_array((int) ($data['id_salon'] ?? 0), $ownedIds, true)) {
    abort(403, 'You can only create discounts for your own salon.');
}
```

### 3h. Admin `PromoResource` update

Tambah field `id_salon` (opsional) di form admin + kolom "Salon" di tabel:

```php
Forms\Components\Select::make('id_salon')
    ->label('Salon (optional)')
    ->relationship('salon', 'nama_salon')
    ->searchable()->preload()->nullable()
    ->helperText('Leave empty for platform-wide promo. Set to restrict to one salon.'),

// Kolom tabel:
Tables\Columns\TextColumn::make('salon.nama_salon')
    ->label('Salon')
    ->placeholder('Platform-wide')  // ← null jadi "Platform-wide"
    ->limit(20)->searchable(),
```

→ Admin bisa lihat di tabel: promo yang punya salon vs platform-wide.

---

## 4. Hasil Smoke Test

```text
Owner: id=3, Salon: id=1 (Hair By Ayesha, London)
Promo created: id=1, code=OWNERTEST0848
Linked salon: Hair By Ayesha, London
byCode lookup: id_salon=1
Other salon: id=2 (Glow 365 Salon)
Would BookingController reject? YES ✓
Would BookingController accept for same salon? YES ✓
Promos visible to owner panel: 1
```

✅ Verifikasi:
1. Promo bisa dibuat dengan `id_salon` linked ke salon owner
2. `Promo::byCode()` mengembalikan promo dengan FK intact
3. Validasi BookingController **menolak** kalau salon beda
4. Validasi BookingController **menerima** kalau salon sama
5. Query scope owner panel cuma return promo milik owner ini (tidak ada platform-wide promo bocor)

---

## 5. State Tabel Sebelum & Sesudah

### Skema Tabel `promo` (sesudah)

```sql
id_promo         BIGINT UNSIGNED  PK
id_salon         BIGINT UNSIGNED  NULL  FK→salon.id_salon  -- ← baru
nama_promo       VARCHAR(150)
deskripsi_promo  TEXT             NULL
diskon           DECIMAL(5,2)
diskon_max       DECIMAL(12,2)    NULL
min_transaksi    DECIMAL(12,2)    DEFAULT 0
tipe_promo       ENUM('percentage','fixed')
kode_promo       VARCHAR(50)      UNIQUE
time_start       TIMESTAMP
time_expired     TIMESTAMP
stock            INT UNSIGNED     DEFAULT 0
used_counter     INT UNSIGNED     DEFAULT 0
status           ENUM('active','inactive','expired')
deleted_at       TIMESTAMP        NULL  (soft delete)
created_at, updated_at
```

### Data flow contoh

**Sebelum implementasi (existing):**
```
| id_promo | id_salon | kode_promo | nama_promo       |
| 1        | NULL     | NEWYEAR    | New Year Special | ← admin promo, semua salon
```

**Sesudah owner create:**
```
| id_promo | id_salon | kode_promo  | nama_promo       |
| 1        | NULL     | NEWYEAR     | New Year Special | ← admin (unchanged)
| 2        | 42       | AYESHA20    | Ayesha 20% off   | ← owner Ayesha
| 3        | 87       | GLOW365     | Glow 365 Special | ← owner Glow 365
```

→ Promo `AYESHA20` cuma valid di salon 42, tidak bisa dipakai di salon 87 atau salon lain.

---

## 6. Booking Flow Validation Matrix

| Skenario | Promo `id_salon` | Customer book salon | Hasil validasi |
|----------|------------------|---------------------|----------------|
| Admin promo, customer book any salon | NULL | any | ✅ valid (platform-wide) |
| Owner promo, customer book same salon | 42 | 42 | ✅ valid |
| Owner promo, customer book different salon | 42 | 87 | ❌ "valid at a different salon" |
| Owner promo, customer book any salon (no id_salon in payload) | 42 | (null) | ❌ rejected (mismatch) |

→ Backward compatible: existing platform-wide promos tetap jalan.

---

## 7. Authorization & Keamanan

| Lapisan | Lokasi | Apa yang dilindungi |
|---------|--------|---------------------|
| Filament panel access | [`User.php:148-159`](../../app/Models/User.php#L148-L159) — `canAccessPanel` | Cuma `role='salon_owner' && is_active=true` yang bisa akses `/owner` |
| Resource query scope | [`PromoResource.php:213-218`](../../app/Filament/Owner/Resources/PromoResource.php#L213-L218) — `getEloquentQuery` | Owner cuma lihat promo dari salon-nya sendiri (via `whereHas('salon', ...)`) |
| Form salon dropdown | Filtered via `Salon::where('id_user', auth()->id())` | Owner tidak bisa pilih salon orang lain di UI |
| Defense-in-depth at submit | `mutateFormDataBeforeCreate` / `mutateFormDataBeforeSave` | Kalau POST tampered dengan `id_salon` orang lain → 403 |
| Booking-side validation | `BookingController::validatePromo` + `store` | Tampered request body tidak bisa pakai promo salon lain |

### Skenario serangan & hasil

| Attack | Hasil |
|--------|-------|
| Customer pakai `AYESHA20` saat book salon Glow 365 | ❌ "valid at a different salon" |
| Customer bypass JS, langsung POST booking pakai promo salon lain | ❌ Ditolak `BookingController::store` |
| Owner A tebak URL `/owner/promos/{B-promo-id}/edit` | ❌ 404 dari query scope filter |
| Owner A submit form dengan `id_salon` = salon owner B | ❌ 403 dari `mutateFormDataBeforeSave` |
| Owner buka admin panel `/admin/promos` | ❌ 403 dari `canAccessPanel` (cuma admin) |

---

## 8. Cara Test Manual

### Test owner create promo

1. Login `/owner/login` (pakai owner yang sudah ada — kalau ada owner test, gunakan pwd default `password`)
2. Sidebar → **Operations → Discounts** → klik **New Discount**
3. Isi form:
   - **Salon** — pilih salon Anda
   - **Discount Name** — "Test Promo"
   - **Code** — "TESTOWN" (auto-uppercase)
   - **Type** — Percentage
   - **Discount** — 10
   - **Max Discount** — 50
   - **Min Transaction** — 0
   - **Start** — sekarang
   - **Expires** — bulan depan
   - **Stock** — 100
   - **Status** — Active
4. Klik **Create** → toast "Created" → kembali ke list
5. Verifikasi di DB:
   ```sql
   SELECT id_promo, id_salon, kode_promo, nama_promo, status
     FROM promo WHERE kode_promo = 'TESTOWN';
   ```

### Test booking pakai owner promo (di salon yang sama)

6. Logout owner, buka salon di public: `/salon/{slug-salon-owner-anda}`
7. Klik **Book Now** → pilih service & tanggal
8. Di kolom promo code, ketik `TESTOWN` → klik Apply
9. Diskon 10% terapply → total berkurang
10. Lanjut booking → Order tersimpan dengan `id_promo` ter-link

### Test rejection di salon lain

11. Buka salon **berbeda**: `/salon/{slug-salon-lain}`
12. Book → pakai promo `TESTOWN`
13. Hasil: error **"This promo code is only valid at a different salon."**

### Test admin view

14. Login admin `/admin/login`
15. **Transactions → Promos** → list semua promo
16. Kolom "Salon" menampilkan nama salon untuk owner-created promos, "Platform-wide" untuk admin promos

---

## 9. Edge Cases yang Sudah Di-handle

| Skenario | Behavior |
|----------|----------|
| Owner punya >1 salon | Dropdown "Salon" di form list semua, owner pilih yang relevan |
| Owner pilih salon orang lain (tampered POST) | 403 dari `mutateFormDataBeforeCreate/Save` |
| Kode promo duplikat (sudah ada owner lain) | Validation `unique(ignoreRecord: true)` — error "Code already taken" |
| `time_expired` < `time_start` | Validation `after('time_start')` — error di form |
| Customer bypass JS pakai promo salon lain | Server `BookingController::store` tetap reject |
| Admin create promo tanpa pilih salon | Tetap valid (platform-wide, existing behavior) |
| Salon di-delete | Promo-nya cascade delete (FK `cascadeOnDelete`) |
| Owner di-deactivate (`is_active=false`) | Panel access blocked → tidak bisa edit promo (existing gating) |

---

## 10. Yang Bisa Di-improve Nanti (Opsional)

| Item | Effort | Catatan |
|------|--------|---------|
| Validasi: kode promo unique per salon (bukan global) | 1 jam | Saat ini global unique, tapi 2 salon mungkin mau pakai "BLACKFRIDAY" mereka masing-masing |
| Promo sharing antar salon owner sama (multi-salon owner) | 30 menit | Tambah dropdown multi-select salon, gunakan pivot baru `promo_salon` |
| Cap maks 1 promo per booking (existing) | sudah ada | Order punya 1 `id_promo` saja |
| Analytics: lihat berapa kali promo dipakai per periode | 2-3 jam | Widget di dashboard owner — count + sum diskon |
| Auto-expire status | 30 menit | Scheduler `php artisan promo:expire` set status=expired kalau lewat tanggal |
| Notifikasi customer kalau promo soon-to-expire | 2 jam | Mailable + observer di Order |

---

## 11. TL;DR

1. **Migration:** tambah `id_salon` nullable ke tabel `promo`
2. **Owner panel:** menu baru "Operations → Discounts", CRUD promo scoped by `id_user` 
3. **Booking flow:** `validatePromo` & `store` reject kalau salon beda (3 layer: JS payload, AJAX validate, full submit)
4. **Admin panel:** field `id_salon` opsional + kolom "Salon" di list
5. **Backward compatible:** promo lama (id_salon=NULL) tetap jalan sebagai platform-wide
6. **3 lapisan authorization:** panel access, query scope, defense-in-depth at submit
7. **Smoke test ✅:** create promo, validate match, reject mismatch, owner scope correct
