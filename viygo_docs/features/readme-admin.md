# VIYGO Enterprise Admin Panel: Architecture & Scalability Whitepaper

Dokumen ini adalah **Blueprint Arsitektur Mendalam** yang menjelaskan fungsionalitas, keamanan, dan strategi skalabilitas ekstrem (High-Availability) dari Panel Admin VIYGO yang dibangun dengan **Filament v5.6** dan **Laravel 13.x**. 

Panel ini tidak didesain sebagai sekadar alat CRUD (Create, Read, Update, Delete) konvensional, melainkan sebagai *Enterprise Administration Hub* yang dipersiapkan untuk memproses jutaan transaksi pengguna (Pelanggan) dan puluhan ribu entitas *Tenant* (Salon Owner) secara konkuren.

---

## 1. Topologi Keamanan & Otorisasi Lanjutan (Advanced RBAC)

Panel admin dilindungi dengan lapisan keamanan multi-tier yang tidak hanya mengandalkan *session cookies* biasa.

### 1.1 Polimorfik Otorisasi Panel (`FilamentUser` Interface)
Logika autentikasi dieksekusi di level aplikasi sebelum request menyentuh *middleware stack* Filament. Pada model `User` (`app/Models/User.php`), implementasi `canAccessPanel` mencegah serangan eksploitasi akses:

```php
public function canAccessPanel(Panel $panel): bool
{
    // Layer 1: Pengecekan ID Panel
    if ($panel->getId() === 'admin') {
        // Layer 2: Role checking & Active state verification
        // Hanya state active=true yang menghindari akun kompromi (compromised accounts) mengakses data
        return $this->role === 'admin' && $this->is_active;
    }
    return false;
}
```

### 1.2 Adaptasi Type-Hinting Filament v5 Strict Mode
Filament v5 menerapkan *strict type-hinting* pada *core traits* (`HasNavigation`, dsb). Kompatibilitas dengan PHP 8.2+ dipaksa menggunakan struktur `string | \UnitEnum | null` pada properti statik:
- `$navigationGroup` dan `$navigationIcon` tidak lagi menerima `?string` secara longgar. Ini mencegah *fatal error* saat fase *booting* aplikasi pada skala produksi.

---

## 2. Struktur Resource & Relasional (Domain-Driven Design)

Navigasi dibagi ke dalam **Bounded Contexts** sesuai prinsip *Domain-Driven Design* (DDD) untuk menghindari *cognitive overload* pada admin.

### A. Konteks Domain: `Marketplace` (Master Data)
1. **`SalonResource`**: Pusat kontrol entitas *tenant*. 
   - Dilengkapi *Action* kustom (`Approve`/`Reject`) untuk *Quality Control* (QC) vendor baru. 
   - Relasi kompleks dipecah menjadi *Relation Managers* (`ServicesRelationManager`, `StaffRelationManager`, `ImagesRelationManager`) dengan metode *Lazy Loading* via tab. Hal ini mencegah *Memory Exhaustion* (memori habis) pada PHP saat memuat salon yang memiliki ratusan layanan.
2. **`ServiceResource`**: Entitas *High-Volume*. 
   - Global Search **secara eksplisit dimatikan** (`$isGloballySearchable = false;`). Pada ukuran data saat ini (190,594 baris) dan target 5 juta baris, *wildcard query* (`LIKE %...%`) pada 3-4 kolom teks akan membunuh CPU database.
3. **`KategoriResource` & `KotaResource`**: Entitas statis pendukung (*Look-up tables*) yang digunakan sebagai parameter indeks pencarian pada frontend.

### B. Konteks Domain: `Transactions` (Ledger & Revenue)
1. **`OrderResource`**: Pembacaan ledger transaksi (*Immutable Read-Only* pada level item). Modifikasi terbatas pada *State Machine Transition* (Pending -> Confirmed -> Success -> Canceled) untuk menjaga integritas data audit.
2. **`ReviewResource`**: Sistem moderasi pasca-transaksi. Mendukung *Bulk State Mutation* (hide/show) untuk menanggulangi *spam attack* atau ulasan bot.
3. **`PromoResource`**: Manajemen *Discount Ledger* dengan algoritma pengecekan stok (Concurrency Control).

---

## 3. Analisis Celah Arsitektur Konvensional (The Flaws)

Meskipun Filament sangat kuat, pendekatan *out-of-the-box* (bawaan) **TIDAK IDEAL** untuk data berskala masif (contoh: 5+ juta transaksi/order). Membiarkan admin mengakses jutaan baris data secara mentah akan menimbulkan efek domino yang melumpuhkan *production*:

### ⚠️ Bahaya 1: Fenomena Deep Pagination (OFFSET Death)
Saat admin membuka halaman `Orders` atau `Services`, query standar `SELECT * FROM table LIMIT 25 OFFSET 500000` akan memaksa MySQL/PostgreSQL memindai (*scan*) dan membuang 500.000 baris pertama sebelum memberikan 25 baris sisanya. Ini menyebabkan *Query Time-Out* dan lonjakan drastis pada I/O disk.

### ⚠️ Bahaya 2: Real-time Aggregation Lock
Mengandalkan metode agregasi `Order::sum('total_pembayaran')` secara *real-time* di Widget Dashboard (`StatsOverview`) akan memicu *Table Scan*. Jika dilakukan bersamaan dengan jutaan pelanggan yang sedang melakukan transaksi *Insert*, ini akan memicu *Transaction Lock Contention* (tabrakan proses baca-tulis).

### ⚠️ Bahaya 3: Unbounded Export Memory Leak
Admin manusia tidak pernah membaca 1 juta baris. Menyajikan *infinite pagination* tanpa filter rentang waktu adalah pola anti-desain (anti-pattern). Mengekspor data dalam jumlah ini menggunakan respon HTTP sinkronus akan menghasilkan error `Allowed memory size of X bytes exhausted`.

---

## 4. Cetak Biru Solusi Ekstrem & Skalabilitas (LLM / Developer Roadmap)

Untuk mengatasi celah di atas dan membawa arsitektur panel admin VIYGO ke kelas enterprise (*High-Availability*), berikut adalah instruksi tingkat lanjut yang **WAJIB diimplementasikan** oleh LLM/Developer berikutnya:

### Tahap 1: Pembatasan Rentang Query (Time-Series Bounding)
**Solusi**: Wajibkan Filter Waktu (Mandatory Date Range Filters) pada `OrderResource` dan `ReviewResource`.
- **Eksekusi**: Override metode `getEloquentQuery()` pada Resource. Jika *state* filter waktu kosong, paksa query untuk memuat interval maksimum 7 hari terakhir, atau gunakan metode `deferLoading()` dari Filament agar tidak mengeksekusi query `SELECT COUNT(*)` sama sekali sebelum filter diterapkan.

### Tahap 2: Transisi ke Keyset Pagination (Cursor) atau Simple Pagination
**Solusi**: Matikan perhitungan total baris.
- **Eksekusi**: Tambahkan `->paginated(false)` dan ganti dengan *Cursor Pagination* bawaan Laravel (menggunakan klausul `WHERE id > X LIMIT 25` yang di-*backup* oleh *B-Tree Index* secara `O(log n)`). Alternatif termudah: Gunakan *Simple Pagination* yang hanya merender tombol "Next/Prev" tanpa menghitung `total()`.

### Tahap 3: Implementasi CQRS (Command Query Responsibility Segregation) pada Dashboard
**Solusi**: Pisahkan Database Transaksional (OLTP) dari Query Analitik (OLAP).
- **Eksekusi**: 
  1. Hapus agregasi *real-time* di `StatsOverview.php`.
  2. Implementasikan sistem *Materialized Views* pada MySQL/PostgreSQL, ATAU
  3. Gunakan *Laravel Task Scheduling* (Cron Job) setiap jam untuk merekap data ke tabel `daily_metrics`. Widget Dashboard hanya diizinkan membaca dari tabel kecil ini.

### Tahap 4: Redis Caching & Asynchronous Queues untuk Export
**Solusi**: Tangani pekerjaan berat di luar Request Lifecycle.
- **Eksekusi**: Semua eksekusi Bulk Action yang masif (seperti mengubah status ribuan Order) dan Export CSV harus diarahkan ke **Laravel Queues** (dengan *Redis* atau *RabbitMQ*). Proses berjalan di latar belakang (asinkronus) dan Filament akan menggunakan *Database Notifications* untuk memberi tahu admin dengan *download link* dari AWS S3 / MinIO setelah file selesai dikompilasi.

### Tahap 5: Integrasi Elasticsearch/Meilisearch (Scout)
**Solusi**: Filament Global Search harus menggunakan mesin pencari berorientasi dokumen.
- **Eksekusi**: Integrasikan *Laravel Scout* dengan Meilisearch. Alihkan konfigurasi `$isGloballySearchable` di Filament agar tidak memicu query SQL `LIKE`, melainkan melakukan HTTP Request Cepat ke instance Meilisearch yang memiliki algoritma pencarian *Fuzzy* dan *Typo-Tolerance* dalam hitungan milidetik.

---

## 5. Deployment & Operasi Command

Panduan eksekusi untuk Environment Produksi / Kubernetes Container:

```bash
# 1. Clear config & optimize autoloader (Mencegah Filament Reflection Error)
php artisan optimize:clear
composer dump-autoload -o

# 2. Re-cache Filament View Components (Crucial setelah update blade/icon)
php artisan filament:cache-components
php artisan view:cache

# 3. Provisioning Admin Account Darurat
# (Dieksekusi via Kubernetes Pod Exec / SSH)
php artisan tinker --execute="
    \$u = \App\Models\User::firstOrNew(['email' => 'admin@viygo.com']);
    \$u->first_name = 'Supreme';
    \$u->last_name = 'Admin';
    \$u->password = bcrypt('secure_password_123');
    \$u->role = 'admin';
    \$u->is_active = true;
    \$u->save();
    echo 'Enterprise Admin Ready.';
"
```

*Dokumen ini merupakan standar panduan arsitektur final untuk tim pengembang internal VIYGO dan AI Agent untuk iterasi pemeliharaan sistem skala besar.*
