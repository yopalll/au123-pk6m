# Laporan Sesi 5 — Integrasi Eksternal

> **Tanggal:** 2026-05-28 · **Agent:** Claude (Opus 4.7) · **Scope:** `06-integrasi.md`

## Deliverable
`docs/technical/06-integrasi.md`:
- **Midtrans produk:** tabel perbedaan vs V1, Snap token (IDR tanpa convert GBP), webhook route + CSRF exception, aturan `gross_amount = Σ item_details`, refund (refund_key unik).
- **api.co.id ongkir:** `config/ongkir.php`, env vars, endpoint+header, `OngkirService` lengkap (cache 1 jam, timeout 5s, roundWeight), aturan anti-manipulasi (weight server-side, tarif divalidasi ulang).
- **DomPDF:** install (belum ada), `InvoiceService` (booking & product), stream, template path, catatan harga GBP vs IDR di invoice booking.
- **Go scraper + Seeder:** struktur scripts/scraper, kontrak JSON, `FreshProductSeeder` (idempotent via fresh_product_id), `AdminStoreSeeder` (property-assignment role karena $guarded), `ForumCategorySeeder`, cara jalankan.
- Ringkasan env vars baru + verifikasi.

## Keputusan & catatan
1. **Webhook produk:** default = endpoint terpisah `/shop/midtrans/webhook` + tambah CSRF exception di bootstrap/app.php. **Alternatif dicatat:** 1 akun Midtrans biasanya 1 Notification URL → bisa pakai single endpoint yang dispatch by prefix kode_order (`VYG-S-` vs `VYG-`). Implementer pilih.
2. **Ongkir:** weight dari cart server-side; tarif yang dipilih client divalidasi ulang saat checkout (re-fetch). Cache key (origin,destination,weight).
3. **Kurs:** scraper USD→IDR (`usd_to_idr_rate` ~16200) ≠ Midtrans `exchange_rate` GBP (~20000). Dua hal berbeda; jangan tertukar.
4. **AdminStoreSeeder:** WAJIB `$u->role = UserRole::ADMIN_STORE` via property (role di $guarded), `first_name`/`last_name` (bukan `name`).
5. **Invoice booking** harga GBP V1 — perlu keputusan tampilan (GBP/IDR). Invoice produk murni IDR.

## Item terbuka
- Keputusan final single vs dual Midtrans webhook (tergantung akun Midtrans nyata).
- Tampilan mata uang invoice booking.
- PDF via Queue untuk volume tinggi → bahas doc 08.

## Berikutnya (Sesi 6)
`07-admin-store-filament.md`: StorePanelProvider (Filament **v5**, id `store`, path `/admin/store`, daftar di bootstrap/providers.php + canAccessPanel cabang store), 9 Resource (Product, ProductCategory, ProductCollection, ProductOrder, ProductReview, Lookbook+slides+items, EmptyReturn approve/reject→poin, ExclusiveContent, ForumModeration), widget dashboard, authorization (admin_store only, scoping tidak perlu), navigationGroups.

**Untuk agent lanjutan:** baca doc 01 §3.4 (panel V1) + doc 04 (model). Catatan: syntax Filament **v5** (Schema, bukan Form v3). Setelah selesai: update `00-INDEX.md` + `reports/sesi-06.md`.
