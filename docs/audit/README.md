# VIYGO — Full Project Audit (Silicon-Valley Grade)

> **Audit run-date:** 2026-05-16
> **Auditor profile:** Senior staff-level reviewer (security, performance, correctness, maintainability)
> **Scope:** Full repository at `d:\VIYGO-FINAL` — Laravel 13 + Filament v6 + Livewire Flux v2 + Midtrans Snap
> **Audience:** Engineering team & follow-up LLM agents tasked with remediation

---

## 0. Cara Pakai Dokumen Ini

Dokumen di folder `audit_docs/` adalah **single source of truth** hasil audit total proyek VIYGO. Setiap file fokus pada satu sumbu temuan dan ditulis agar bisa dieksekusi oleh agent LLM lain *tanpa harus membaca ulang seluruh kode*:

| File | Isi | Pembaca utama |
|------|-----|---------------|
| [`01-bugs.md`](01-bugs.md) | Bug aktif yang masih reproducible. Setiap entri punya: lokasi `file:line`, gejala, root-cause, langkah-repro, patch yang diusulkan. | LLM/dev yang menambal bug. |
| [`02-optimizations.md`](02-optimizations.md) | Rekomendasi optimalisasi performa, struktur kode, DX, query, dan dependency. Bukan bug — tapi *should-fix*. | LLM/dev refactor/cleanup. |
| [`03-anomalies.md`](03-anomalies.md) | Kode "bau" / inkonsistensi / dead-code / file sampah yang menyulitkan onboarding tapi tidak meledak. | LLM/dev yang merapikan repo. |
| [`04-security.md`](04-security.md) | Temuan khusus security (secrets, mass-assignment, CSRF, debug, webhook integrity, supply-chain). | LLM/dev security hardening. |
| [`05-action-plan.md`](05-action-plan.md) | Urutan eksekusi yang sudah diprioritaskan (P0/P1/P2). Bisa langsung dipakai sebagai task list. | LLM/dev eksekusi. |

> **Aturan**: Saat menutup temuan, tandai checkbox di `05-action-plan.md`. Jangan menghapus entri — pindahkan ke bagian "Closed" agar histori audit terjaga.

---

## 1. Ringkasan Eksekutif

VIYGO adalah marketplace Treatwell-clone dengan tech-stack modern (Laravel 13, Filament v6, Livewire Flux v2, Midtrans). Codebase relatif konsisten, sudah ada `App\Constants\OrderStatus`, dokumentasi yang banyak, dan fitur inti (booking 3-step + payment + review + dual Filament panel) sudah berjalan. Audit ini menemukan:

| Kategori | Critical | High | Medium | Low | Total |
|----------|:--------:|:----:|:------:|:---:|:-----:|
| Bug              | 3 | 6 | 5 | 4 | **18** |
| Security         | 2 | 3 | 2 | 1 |  **8** |
| Optimization     | 0 | 2 | 6 | 4 | **12** |
| Anomaly / Code smell | 0 | 1 | 6 | 7 | **14** |
| **Total**        | **5** | **12** | **19** | **16** | **52** |

**Top 5 risiko paling mendesak (P0):**

1. **`.env` di-commit dengan APP_KEY dan MIDTRANS_SERVER_KEY asli** → rotasi kredensial wajib.
2. **`APP_DEBUG=true` + APP_URL ngrok publik** → stack-trace bocor ke internet (lihat screenshot `eror_v1/`).
3. **`User::$fillable` mengandung `role` & `is_active`** → mass-assignment privilege-escalation risk.
4. **`DatabaseSeeder::run()` melakukan `truncate` 11 tabel inti** tanpa konfirmasi — sekali jalan di production = data wipe.
5. **Race condition booking**: tidak ada UNIQUE constraint di level DB pada `(id_staff, date_order, start_time)` — dua user race-condition bisa double-book slot meski ada server-side check.

**Anomali utama:**
- File "sampah" di root: `clean_md.php`, `test-json.php`, `test_panel_routes.php`, folder `update/`, `eror_v1/`, `eror_v2/`. Tidak boleh ikut deploy.
- Dual-source dokumentasi (`docs/` vs `viygo_docs/`) dengan isi tumpang-tindih — perlu konsolidasi.
- `Order::scopePending` / `scopeSuccess` masih hard-code string padahal `OrderStatus` constant sudah ada.

---

## 2. Inventory Singkat (apa yang di-audit)

```
Backend
├── 14 Controllers di app/Http/Controllers
├── 15 Models di app/Models (Order, Salon, Service, Staff, User, ...)
├── 1 Service (BookingSlotService)
├── 1 Console Command (CompleteBookings — scheduled daily 01:00)
├── 1 Middleware (CheckRole)
├── 1 Observer (ReviewObserver)
├── 2 Fortify Actions (CreateNewUser, ResetUserPassword)
├── Filament: 9 Admin Resources + 4 Owner Resources + 3 Widgets
└── Migrations: 30 file (terutama 2026_04_12_*, ada 4 hotfix migration)

Frontend
├── resources/views: ~30 blade (akun, booking, salon, cari, kategori, mitra, static)
├── Komponen reusable: viygo-navbar, viygo-footer, viygo-logo, salon-card, leaflet-map
├── Booking 3-step wizard berbasis Alpine.js murni (bukan Livewire)
└── Public layout single-file dengan Leaflet via CDN

Integrasi
├── Midtrans Snap (Sandbox) — server-key + client-key + webhook signature
├── Laravel Fortify (2FA-ready)
├── Filament v6.5/6
└── Vite + TailwindCSS v4

Database
├── Engine: MySQL 8
├── 30 tabel (incl. enum hotfixes untuk status order)
├── Soft-delete: User, Salon, Service, Staff
└── Dataset live: 5,767 salon, ±190K services dari scraper Treatwell
```

---

## 3. Metodologi Audit

1. **Sweep dokumentasi**: README + `docs/` + `viygo_docs/bugs/REPORT-PHASE-1..4` + `viygo_docs/reports/LAPORAN_PROYEK.md` + `docs/PROJECT-ANALYSIS.md`. Hasilnya jadi konteks "fitur yang dijanjikan" untuk validasi vs realita kode.
2. **Static review per modul** mulai dari `routes/web.php` (entry point), turun ke Controller → Service → Model → Migration.
3. **Cross-check** antara Migration enum vs Constant kelas vs string yang dipakai di Controller.
4. **Audit konfigurasi**: `.env` aktual, `config/services.php`, `bootstrap/app.php`, `composer.json`.
5. **Audit blade**: `booking/create.blade.php`, `payment.blade.php`, `akun/bookings.blade.php`, `viygo-navbar.blade.php`, `home.blade.php` (yang paling kompleks / risk-bearing).
6. **Audit screenshot error** di `eror_v1/` & `eror_v2/` — diverifikasi terhadap kode terkini.
7. **Klasifikasi**: bug | security | optimization | anomaly. Lalu prioritisasi P0/P1/P2.

Semua temuan menyertakan:
- File path absolut + line number.
- Snippet code "before".
- Patch / fix yang diusulkan (siap-tempel).
- Reason / dampak jika tidak diperbaiki.

---

## 4. Status Catatan Audit Sebelumnya

Folder `viygo_docs/bugs/REPORT-PHASE-1..4.md` menutup BUG-01 s/d BUG-12 (Mei 2026). Audit ini mengonfirmasi:

| Bug lama | Status terverifikasi 2026-05-16 |
|----------|--------------------------------|
| BUG-01 (enum `confirmed`) | ✅ Migration `2026_05_02_110000_*` ada & diterapkan. |
| BUG-02 (Vite manifest) | ⚠️ README sudah update; tapi `eror_v1` screenshot lama menunjukkan masih kena di lingkungan baru. Mitigasi via dokumentasi saja, belum di-CI. |
| BUG-03/04 (status filter) | ✅ `AkunController` & `OwnerStatsOverview` pakai constant. |
| BUG-05 (canceled spelling) | ✅ Migration enum standarisasi. |
| BUG-06 (day-of-week casing) | ✅ Verified. |
| BUG-07 (refund) | ✅ `BookingController::batal()` triggers `Transaction::refund`. **Catatan:** masih ada limitasi (lihat BUG-A06). |
| BUG-08 (calendar) | ✅ Verified — sudah pakai `selectedYear/selectedMonth` di Alpine. |
| BUG-09/10 (BookingSlot empty roster / staff_service pivot) | ✅ Verified. |
| BUG-11 (admin OrderResource enum) | ✅ Constants. |
| BUG-12 (Vite docs) | ✅ README final. |

Tapi audit **menemukan 18 bug baru / yang sebelumnya tidak terdaftar** (lihat `01-bugs.md`).

---

## 5. Lihat Juga

- [`01-bugs.md`](01-bugs.md)
- [`02-optimizations.md`](02-optimizations.md)
- [`03-anomalies.md`](03-anomalies.md)
- [`04-security.md`](04-security.md)
- [`05-action-plan.md`](05-action-plan.md)
