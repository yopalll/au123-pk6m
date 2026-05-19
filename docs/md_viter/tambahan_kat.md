# Laporan: Owner Bisa Pilih Kategori & Sub-Kategori Saat Edit Salon

> 📌 **Status: ✅ Implementasi Selesai**
> Owner sekarang punya 2 multi-select di halaman edit salon untuk memilih kategori utama (Hair, Nails, dll) dan sub-kategori (Blow Dry, Pedicure, dll). Pilihan otomatis tersimpan ke pivot table `salon_kategori` dan `salon_sub_kategori`.
>
> Lihat juga: [`Approval_Admin2.md`](Approval_Admin2.md) untuk alur approval salon yang sudah ada.

---

## 1. Apa yang Diubah (Ringkas)

**Sebelum:** Owner panel edit salon cuma punya field Basic Info, Location, Contact & Hours, dan Metrics (read-only). Kategori salon **hanya** bisa di-isi oleh scraper Treatwell saat seeding awal — owner tidak punya akses untuk update.

**Sesudah:** Tambah section **"Categories"** di form edit dengan 2 multi-select:
- **Kategori Utama** — pilih dari 7 kategori utama (Hair, Hair Removal, Massage, Nails, Face, Body, Men's)
- **Sub-Kategori (Treatment)** — pilih dari 42 sub-kategori spesifik

Owner bisa pilih banyak (multi-select) dan filter dengan typing (searchable). Saat save, Filament otomatis menulis ke pivot table — owner tidak perlu paham SQL atau ID.

---

## 2. File yang Diubah

| File | Status | Tujuan |
|------|--------|--------|
| [`app/Filament/Owner/Resources/SalonResource.php`](../../app/Filament/Owner/Resources/SalonResource.php) | **Diubah** | Tambah section "Categories" dengan 2 multi-select reactive ke pivot table |

**Tidak ada migration** karena tabel pivot `salon_kategori` & `salon_sub_kategori` sudah ada dari migration sebelumnya:
- [`2026_05_10_000004_create_salon_kategori_table.php`](../../database/migrations/2026_05_10_000004_create_salon_kategori_table.php)
- [`2026_05_10_000005_create_salon_sub_kategori_table.php`](../../database/migrations/2026_05_10_000005_create_salon_sub_kategori_table.php)

**Tidak ada perubahan model** karena `Salon::kategoris()` dan `Salon::subKategoris()` sudah didefinisikan sebagai `belongsToMany` di [`Salon.php:69-91`](../../app/Models/Salon.php#L69-L91).

---

## 3. Detail Code yang Ditambahkan

### Section "Categories" di Form

```php
\Filament\Schemas\Components\Section::make('Categories')
    ->description('Pilih kategori utama (mis. Hair, Nails) dan sub-kategori (mis. Blow Dry, Pedicure) yang ditawarkan salon Anda. Customer mencari salon berdasarkan kategori ini.')
    ->schema([
        Forms\Components\Select::make('kategoris')
            ->label('Kategori Utama')
            ->relationship(
                name: 'kategoris',
                titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name'),
            )
            ->multiple()
            ->preload()
            ->searchable()
            ->helperText('Misal: Hair, Nails, Massage. Bisa pilih lebih dari satu.'),

        Forms\Components\Select::make('subKategoris')
            ->label('Sub-Kategori (Treatment)')
            ->relationship(
                name: 'subKategoris',
                titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name'),
            )
            ->multiple()
            ->preload()
            ->searchable()
            ->helperText('Treatment spesifik. Misal: Blow Dry, Pedicure, Manicure.'),
    ])->columns(1),
```

### Penjelasan Tiap Method

| Method | Fungsi |
|--------|--------|
| `->relationship(name: ...)` | Filament tahu ini relasi belongsToMany — auto handle pivot table |
| `titleAttribute: 'name'` | Kolom yang ditampilkan di dropdown (nama kategori) |
| `modifyQueryUsing: fn ($q) => ...` | Filter opsi: hanya `is_active=true`, di-sort alfabet |
| `->multiple()` | Multi-select (bisa pilih banyak) |
| `->preload()` | Load semua opsi saat form open (bagus untuk dataset kecil <100 row) |
| `->searchable()` | Owner bisa typing untuk filter dropdown |
| `->helperText('...')` | Petunjuk kecil di bawah field |

---

## 4. Bagaimana Pivot Write Bekerja

Filament's `Select::multiple()->relationship()` di belakang layar memanggil `$model->$relation()->sync([...ids])`. Jadi:

```php
// Yang Filament jalankan saat owner klik Save:
$salon->kategoris()->sync([1, 2, 5]);       // ← pilihan owner: 3 kategori
$salon->subKategoris()->sync([3, 7, 12]);   // ← pilihan owner: 3 sub-kategori
```

`sync()` adalah method Laravel built-in yang:
1. **Hapus** pivot row yang tidak ada di array baru
2. **Insert** pivot row baru yang belum ada
3. **Pertahankan** pivot row yang sudah ada
4. **Otomatis isi timestamp** karena relasi pakai `->withTimestamps()`

→ Tidak ada duplikasi, tidak ada manual loop, atomic per relation.

### Diagram Aliran Data

```
[Owner buka /owner/salons/{id}/edit]
        │
        ▼
[Filament render form]
        │
        ├─ SELECT * FROM salon_kategori WHERE id_salon=N
        │  → pre-fill multi-select "Kategori Utama" dengan chip yang sudah dipilih
        │
        └─ SELECT * FROM salon_sub_kategori WHERE id_salon=N
           → pre-fill multi-select "Sub-Kategori"

[Owner ubah pilihan + klik Save]
        │
        ▼
[Filament EditRecord::save()]
        │
        ├─ UPDATE salon SET ... (field biasa)
        │
        ├─ $salon->kategoris()->sync([3, 5, 7])  ← Filament panggil
        │   → DELETE FROM salon_kategori WHERE id_salon=N AND id_kategori NOT IN (3,5,7)
        │   → INSERT INTO salon_kategori (id_salon, id_kategori, created_at, updated_at)
        │     VALUES (N, 3, NOW(), NOW()), (N, 5, NOW(), NOW()), (N, 7, NOW(), NOW())
        │
        └─ $salon->subKategoris()->sync([12, 18])  ← Filament panggil
            → DELETE FROM salon_sub_kategori WHERE id_salon=N AND id_sub_kategori NOT IN (12,18)
            → INSERT INTO salon_sub_kategori ...

[Toast "Saved" muncul]
        │
        ▼
[Salon sekarang muncul di /kategori/{slug} dan /sub-kategori/{slug}
 berdasarkan pilihan baru]
```

---

## 5. Smoke Test

Test dilakukan via `php artisan tinker` untuk verifikasi behavior `sync()` (yang Filament panggil):

```text
Before: kategoris=1, subKategoris=4    ← state dari seed

[$salon->kategoris()->sync([1, 2])]
[$salon->subKategoris()->sync([1, 2, 3])]

After sync:
  kategoris=2 (ids: 1,2)
  subKategoris=3 (ids: 1,2,3)
Pivot rows: salon_kategori=2, salon_sub_kategori=3 ✅
```

✅ Verifikasi:
1. Pivot row lama yang tidak di-pilih ulang → **dihapus** otomatis
2. Pivot row baru → **di-insert** dengan timestamp
3. Pivot row yang masih ada → **dipertahankan** (tidak duplicate)
4. Count pivot table = count array yang di-sync

---

## 6. Authorization & Keamanan

Tidak ada perubahan keamanan diperlukan — gating yang sudah ada cukup:

| Lapisan | Lokasi | Apa yang dilindungi |
|---------|--------|---------------------|
| Filament panel access | [`User.php:148-159`](../../app/Models/User.php#L148-L159) — `canAccessPanel` | Cuma user dengan `role='salon_owner'` & `is_active=true` yang bisa akses `/owner` |
| Resource query scope | [`SalonResource.php:163-168`](../../app/Filament/Owner/Resources/SalonResource.php#L163-L168) — `getEloquentQuery` | Owner A tidak bisa edit salon owner B (filter `id_user = auth()->id()`) |
| URL tampering | Same — query scope filter applies to `/owner/salons/{id}/edit` URL | Filament return 404 kalau record tidak match query scope |

Skenario kalau owner coba bypass:
- Owner A buka URL `/owner/salons/{salon-B-id}/edit` → 404 (query scope filter)
- Customer biasa buka `/owner/salons/.../edit` → redirect ke login (panel access)
- Guest buka URL → redirect ke `/owner/login`

---

## 7. Implikasi ke Sistem Discovery

Setelah owner pilih kategori, salon **otomatis muncul** di pencarian publik berdasarkan kategori tersebut. Mekanismenya:

### Untuk halaman `/kategori/{slug}` (mis. `/kategori/hair`)

Di [`KategoriController.php:34-42`](../../app/Http/Controllers/KategoriController.php#L34-L42):

```php
$query = Salon::active()
    ->whereHas('kategoris', fn ($k) => $k
        ->where('kategori.id_kategori', $kategori->id_kategori))
    ...
```

→ Filter pakai pivot `salon_kategori` langsung. Salon yang di-`sync` dengan kategori tersebut akan muncul.

### Untuk halaman `/sub-kategori/{slug}` (mis. `/sub-kategori/blow-dry`)

Di [`KategoriController.php:88-91`](../../app/Http/Controllers/KategoriController.php#L88-L91):

```php
$salons = Salon::active()
    ->whereHas('subKategoris', fn ($s) => $s
        ->where('sub_kategori.id_sub_kategori', $sub->id_sub_kategori))
    ...
```

→ Filter pakai pivot `salon_sub_kategori`. Sama, owner pick = otomatis ter-include.

> ⚠️ **Catatan:** salon harus `status='active'` dulu (via `scopeActive`). Salon yang baru di-approve admin (`status='inactive'`) **tidak akan muncul** walaupun sudah pilih kategori — owner harus tunggu admin activate dulu.

---

## 8. Cara Test Manual

1. Login ke `/owner/login` (pakai email + password owner)
2. Sidebar → **Salon → My Salon** → klik salon → **Edit**
3. Scroll ke section **"Categories"** (di antara "Contact & Hours" dan "Metrics")
4. Pilih beberapa kategori (mis. "Hair", "Nails") + beberapa sub-kategori (mis. "Blow Dry", "Manicure")
5. Klik **Save** → toast "Saved" muncul
6. Refresh halaman → chip yang dipilih tetap tampil (form re-hydrate dari pivot)
7. Verifikasi di DB:
   ```sql
   SELECT k.name FROM salon_kategori sk
     JOIN kategori k ON k.id_kategori = sk.id_kategori
     WHERE sk.id_salon = <id_salon_anda>;

   SELECT s.name FROM salon_sub_kategori ss
     JOIN sub_kategori s ON s.id_sub_kategori = ss.id_sub_kategori
     WHERE ss.id_salon = <id_salon_anda>;
   ```
8. Buka halaman publik `/kategori/hair` (atau slug kategori yang Anda pilih) — salon harus muncul kalau `status='active'`
9. Test edit lagi: hapus 1 kategori, tambah 2 sub-kategori baru → Save → cek pivot count berkurang/bertambah sesuai

---

## 9. Edge Cases & Behavior

| Skenario | Behavior |
|----------|----------|
| Owner tidak pilih kategori apapun → save | Semua pivot row untuk salon ini di-delete. Salon tidak muncul di kategori manapun. |
| Owner pilih kategori yang `is_active=false` | Tidak mungkin — `modifyQueryUsing` filter opsi dropdown |
| Admin nonaktifkan kategori (`is_active=false`) setelah owner pilih | Pivot row tetap ada, tapi opsi hilang dari dropdown kalau owner edit lagi. Untuk discovery, tergantung apakah `Kategori::scopeActive` dipakai di query. |
| Owner pilih kombinasi "aneh" (Hair + Massage tapi sub-kategori semuanya Nails) | Tidak ada validasi — boleh saja, owner mungkin offering treatment cross-category. |
| Banyak salon edit pivot bersamaan | `sync()` atomic per call, tidak ada race condition antar salon (beda `id_salon`). |
| Owner edit form tapi tutup tab tanpa save | Tidak ada perubahan ke pivot — Filament hanya write saat `save()`. |

---

## 10. Yang BISA Di-improve Berikutnya (Opsional)

| Improvement | Effort | Catatan |
|-------------|--------|---------|
| Sub-kategori reactive ke kategori (filter cascade) | 1-2 jam | Pakai `live()` + `modifyOptionsQueryUsing` dengan join ke `kategori_sub_kategori`. Hanya tampilkan sub yang related ke kategori terpilih. |
| Validasi minimum 1 kategori sebelum activate | 30 menit | Tambah rule di controller validation saat admin try set `status='active'`. |
| Tampilkan badge count kategori di table list | 15 menit | `Tables\Columns\TextColumn::make('kategoris_count')->counts('kategoris')` di table. |
| Auto-suggest kategori dari nama service | 2-3 jam | Saat owner add service, infer kategori-nya berdasarkan service.id_kategori. |
| Audit log saat owner ubah kategori | 1 jam | `Salon::observe(...)` → log perubahan pivot ke tabel `audit_log`. |

---

## 11. TL;DR

- Tambah section "Categories" di owner panel edit salon dengan 2 multi-select (kategori + sub-kategori)
- Filament otomatis tulis ke pivot `salon_kategori` & `salon_sub_kategori` via `sync()`
- Tidak perlu migration / model change — semua infrastruktur sudah ada
- Smoke test pivot write ✅
- Salon yang pilih kategori otomatis muncul di pencarian `/kategori/{slug}` dan `/sub-kategori/{slug}` (selama `status='active'`)
- Authorization aman lewat scoping `getEloquentQuery` yang sudah ada
