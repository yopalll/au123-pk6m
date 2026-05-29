# Laporan Sesi 3 — Eloquent Models & Constants V2

> **Tanggal:** 2026-05-28 · **Agent:** Claude (Opus 4.7) · **Scope:** `04-eloquent-models.md`

## Deliverable
`docs/technical/04-eloquent-models.md`:
- **8 Constants class** baru + update `UserRole` (tambah `ADMIN_STORE`): ProductOrderStatus, ProductPaymentStatus, EmptyReturnStatus, PointTier (+`fromPoints()`/THRESHOLD), ForumStatus, BadgeSlug, CommunityPoints.
- **Update `User`**: cabang `store` di `canAccessPanel()`, 16+ relasi V2, helper `tier()`/`pointBalance()`.
- **28 model V2**: kode penuh untuk Product (cast/scope/accessor), ProductOrder, relasi morph forum; sisanya tabel spesifikasi ringkas (table/PK/fillable/casts/relasi).
- **5 Observer** (ProductReview→rating, EmptyReturn→poin, Forum thread/reply/like→poin/badge) — hook ke service di doc 05.
- **morphMap** untuk polymorphic (forum_likes, point_transactions).

## Keputusan & catatan penting
1. **Cast SET `skin_type`:** JANGAN pakai cast `'array'` bawaan (itu untuk JSON, bukan SET MySQL). Rekomendasi: kolom `string` + custom `CommaSeparated` cast → portable ke SQLite test. **Harus disinkronkan dengan doc 03 §11** (kalau dipilih string, ubah blueprint `products.skin_type` dari `set()` jadi `string()`).
2. **morphMap** wajib di `AppServiceProvider::boot()` agar `likeable_type` = `'forum_thread'`/`'forum_reply'` (sesuai nilai di doc 03), bukan FQCN.
3. **`UPDATED_AT=null`** untuk Wishlist/ForumLike/ForumBookmark (migration created_at-only).
4. **Money decimal cast → string**; aritmetika di service pakai float/bcmath.
5. Observer counter pakai `increment()`/`decrement()` (hindari race); detail transaksi di doc 05.

## Item terbuka (follow-up)
- Finalisasi pilihan SET vs string untuk `skin_type` → update doc 03 bila perlu (saat ini doc 03 masih `set()`, doc 04 merekomendasikan string). **Agent berikut: putuskan & samakan kedua doc.**
- `id_product` onDelete di order_items/reviews — doc 03 pakai restrict; konfirmasi tidak bentrok dengan cascade test.

## Berikutnya (Sesi 4)
`05-backend-services-controllers.md`:
- Daftar controller + signature method per route (PRD §4.4/§5.4/§6.4/§7.4/§8.4).
- Service layer detail: OrderCodeGenerator, CartService, OngkirService, CheckoutService (transaksi lengkap), ProductPaymentService, PointService, PromoService (reuse Promo V1), InvoiceService, Community/Badge service.
- FormRequest validasi (CheckoutRequest, dll.).
- State machine transisi status (guard transisi ilegal).
- Free-ongkir + tier logic, poin 1:1000, snapshot harga.

**Untuk agent lanjutan:** baca doc 03 + 04 dulu. Setelah selesai: update `00-INDEX.md` §1 + Session Log + `reports/sesi-04.md`.
