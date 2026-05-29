# Laporan Sesi 2 — Database Schema & Migrations V2

> **Tanggal:** 2026-05-28
> **Agent:** Claude (Opus 4.7)
> **Scope:** Tulis `03-database-schema-migrations.md`.

## Deliverable
`docs/technical/03-database-schema-migrations.md` — spesifikasi penuh **1 ALTER + 28 CREATE migration**:
- Migration #0 ALTER `users.role` (full code, up/down).
- Urutan kanonik 29 migration (Batch A–G, induk→anak) dalam tabel.
- Blueprint Laravel siap-pakai untuk **semua 28 tabel** (kolom, tipe, FK eksplisit, onDelete, index, unique).
- Ringkasan index & unique global.
- Catatan tipe data (SET, JSON, polymorphic, enum vs Constants).
- Peringatan kompatibilitas test SQLite (SET/FULLTEXT/ALTER ENUM MySQL-only).
- Perintah verifikasi (acceptance Phase 1).

## Keputusan desain & koreksi PRD
1. **Reorder `product_reviews`** dari Batch C → Batch D (urut 19, setelah `product_orders` urut 16). Menghilangkan "deferred FK" yang ada di PRD §18. Jumlah tabel tetap 28.
2. **Money `decimal(12,2)`** (ikut V1), bukan `decimal(10,2)` (PRD).
3. **Kolom tambahan di luar PRD** (alasan: integritas/audit, meniru pola V1):
   - `product_pembayaran.raw_response` (json) — audit webhook (pola `pembayaran.raw_response` V1).
   - `product_reviews.is_visible` — moderasi (pola `review.is_visible` V1).
   - `products`: unique `fresh_product_id` (seeder idempotent) + FULLTEXT(nama,deskripsi,key_ingredients) untuk `/shop/cari`.
   - `product_order_items`: snapshot `nama_produk`/`harga_satuan`/`berat_gram` agar histori akurat.
4. **`product_pembayaran.status`** beda nama dari V1 (`pembayaran.status_pembayaran`) — dicatat agar model/service konsisten.
5. **`skin_type` = MySQL SET** — diberi warning soal driver test.

## Hal yang belum diputuskan (untuk sesi berikut / doc 08)
- Driver DB untuk test (MySQL vs SQLite) — memengaruhi SET/FULLTEXT/ALTER ENUM. Putuskan di doc 08.
- `id_product` di `product_order_items` & `product_reviews`: pilih `restrictOnDelete` vs `nullable+nullOnDelete`. Saat ini doc menyarankan restrict untuk items. Konfirmasi saat tulis model (doc 04).

## Berikutnya (Sesi 3)
`04-eloquent-models.md`:
- 28 model V2: `$table`, `$primaryKey`, `$fillable`/`$guarded`, `casts()`, relasi lengkap (belongsTo/hasMany/belongsToMany/morph).
- Update `User` (relasi V2: addresses, carts, wishlists, productOrders, points, badges, threads, dll. + cabang `store` di `canAccessPanel` + `UserRole::ADMIN_STORE`).
- Cast SET `skin_type` & JSON.
- Scope yang berguna (Product::active/featured, ForumThread::published, dll.).
- Observer hooks: `ProductReviewObserver` (recalc rating), poin & badge (referensi ke doc 05 untuk service).
- Constants classes baru (ProductOrderStatus, ProductPaymentStatus, PointTier, EmptyReturnStatus, ForumStatus, BadgeSlug).

**Untuk agent lanjutan:** baca `00-INDEX.md` (§2,§3) + doc 03 sebelum tulis model agar nama kolom/PK persis. Setelah selesai, update checklist `00-INDEX.md` §1 + Session Log + buat `reports/sesi-03.md`.
