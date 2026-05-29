# Laporan Sesi 4 — Backend: Controllers, Services & Business Logic

> **Tanggal:** 2026-05-28 · **Agent:** Claude (Opus 4.7) · **Scope:** `05-backend-services-controllers.md`

## Deliverable
`docs/technical/05-backend-services-controllers.md`:
- **Mapping route→controller→service** untuk Modul 1–5 (method signature, middleware, service yang dipanggil).
- **Service layer (12):** OrderCodeGenerator, CartService, OngkirService, PromoService (reuse Promo V1), PointService, **CheckoutService** (kode transaksi DB lengkap + lock stok + ongkir+promo+poin), ProductPaymentService (Midtrans tanpa convert GBP), InvoiceService, ForumService, ForumInteractionService, BadgeService, CommunityPointService.
- **FormRequest** (7) + aturan anti-manipulasi (berat/subtotal server-side).
- **State machine** `ProductOrderState` + transisi per-aktor + state machine empty_return & payment.
- **Aturan bisnis:** free-ongkir+tier (Silver 1×/bln, Gold unlimited), poin 1:1000, stok/oversell, review guard, scheduler.
- Tabel reuse & perbedaan V1 vs V2.

## Keputusan desain penting
1. **Stok dikurangi saat order DIBUAT (pending)**, bukan saat paid → cegah oversell. Konsekuensi: butuh scheduler `CancelStaleProductOrders` (kembalikan stok + refund poin saat pending kedaluwarsa). Tercatat di doc.
2. **Berat & subtotal dihitung server-side dari cart** — client hanya kirim destination/kurir/layanan/promo/poin. Anti-manipulasi ongkir & harga.
3. **ProductPaymentService terpisah** dari PaymentController V1; `gross_amount` = grand_total IDR (tanpa convertGbpToIdr). item_details harus Σ = grand_total (baris ongkir + diskon/poin negatif).
4. **Poin debit saat checkout**, refund saat cancel/expired.
5. **Reconcile lintas-doc:** `products.skin_type` diselaraskan jadi `string` di doc 03 (sebelumnya `set()`), sesuai rekomendasi doc 04. Kedua doc kini konsisten.

## Item terbuka
- Detail teknis Midtrans produk (route webhook + CSRF exception path), format api.co.id, template DomPDF, Go scraper → **doc 06 (sesi 5)**.
- Free-ongkir Silver "1×/bln": butuh cara hitung pemakaian — doc menyarankan derive dari product_orders bulan berjalan; finalisasi implementasi saat coding.

## Berikutnya (Sesi 5)
`06-integrasi.md`: Midtrans produk (config reuse, webhook, signature), api.co.id ongkir (endpoint, header, cache, timeout, OngkirService impl), DomPDF (install, template, stream), Go scraper (config.json, output JSON, FreshProductSeeder idempotent, AdminStoreSeeder dgn property-assignment role, ForumCategorySeeder), env vars baru.

**Untuk agent lanjutan:** baca doc 01 §6 (Midtrans V1), doc 05 §2.7 & §2.3. Setelah selesai: update `00-INDEX.md` + `reports/sesi-05.md`.
