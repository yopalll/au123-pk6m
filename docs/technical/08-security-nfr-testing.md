# 08 — Security, Non-Functional Requirements & Testing

> **Tujuan:** Menutup spesifikasi backend V2 dengan: keamanan (auth/authz, rate limit, upload, XSS, payment), performa (cache, queue, index, N+1), dan **strategi testing** (Pest, mock integrasi, keputusan DB test). Acuan: PRD §15, audit V1 di `docs/audit/`.

---

## 1. Security

### 1.1 Auth & Authorization (reuse V1)
| Aspek | Implementasi |
|-------|--------------|
| Auth | Fortify (login/register/2FA). Route butuh login: `['auth','verified']`. |
| Role gate | Middleware `role:` (`CheckRole`) — route customer V2: `role:customer`. Panel store: `canAccessPanel` (doc 07). |
| Active guard | `EnsureUserIsActive` global (append `web`) — user nonaktif ter-logout. |
| Mass-assignment | `User::$guarded=['role','is_active','id_user']`. Model V2 tanpa kolom sensitif (aman pakai $fillable). |
| IDOR | Semua query pesanan/alamat/invoice **scope ke `auth()->id()`** (`where('id_user',auth()->id())->firstOrFail()`), pola V1. Jangan trust `{kode}`/`{id}` saja. |

> Tambah `UserRole::ADMIN_STORE` ke validasi role di mana pun V1 memvalidasi (mis. rule `in:`+`UserRole::all()`).

### 1.2 Rate Limiting (PRD §15) — pola `throttle:` V1
| Endpoint | Limit |
|----------|-------|
| Submit (review, empty return, thread, reply) | `throttle:5,1` |
| Browse/list publik | `throttle:30,1` (atau default) |
| Ongkir check | `throttle:10,1` |
| Cart/wishlist toggle | `throttle:30,1` |
| Webhook Midtrans (salon+produk) | `throttle:120,1` (generous utk retry) |

### 1.3 File Upload (review foto, empty return foto, gambar produk/lookbook)
- Validasi di FormRequest: `mimes:jpg,jpeg,png,webp`, `max:2048` (2MB), jumlah maks (review/empty return: 3).
- Simpan ke `storage/app/public/...` + `php artisan storage:link`. Bisa migrasi ke S3/GCS nanti (PRD §15).
- Nama file di-randomize (`store()`), jangan pakai nama asli user.

### 1.4 XSS — rich text forum & konten eksklusif
- Konten thread/reply & exclusive article = rich text → **sanitasi server-side** sebelum simpan (HTMLPurifier, mis. `mews/purifier`) atau render dengan escaping ketat + allowlist tag.
- PRD §15 sebut HTMLPurifier. Tambah paket bila dipakai. Jangan `{!! !!}` mentah tanpa sanitasi.

### 1.5 Payment & Webhook (kritis)
- **Signature SHA512** wajib di webhook produk (doc 06 §1.4). Tolak mismatch (403).
- **Idempotency** (transaction_id + status). **`lockForUpdate`** serialisasi webhook.
- **Verifikasi server-side** status (`Transaction::status`) di `finish()`, jangan percaya callback frontend.
- API key (`API_CO_ID_KEY`, Midtrans keys) hanya di `.env`, **jangan commit**. `.env.example` hanya placeholder kosong.
- Validasi ulang **harga & ongkir server-side** saat checkout (doc 05 §3) — jangan percaya angka dari client.

### 1.6 CSRF
- Default Laravel aktif. Pengecualian hanya webhook (`midtrans/webhook`, `shop/midtrans/webhook`) di `bootstrap/app.php`.

---

## 2. Performance & Scalability (PRD §15)

### 2.1 Caching
| Data | Strategi | TTL |
|------|----------|-----|
| Ongkir result | `Cache::remember` key (origin,destination,weight) | 1 jam |
| Katalog `/shop` (featured, kategori, koleksi) | cache fragment/query | 15 menit |
| Lookbook index | cache | 15 menit |
| Forum leaderboard | cache | 15–60 menit |
- Driver: file (dev) / Redis (prod). **Invalidasi**: saat produk/lookbook diubah lewat Filament → `Cache::forget` (observer atau di Resource `afterSave`).

### 2.2 Queue
- Email notifikasi (konfirmasi pesanan, status kirim), generate PDF batch → `ShouldQueue`. V1 sudah punya `jobs` table + `queue:listen` di `composer dev`.
- Invoice on-demand (stream) tetap sinkron (cukup cepat). Queue untuk volume tinggi/email lampiran.

### 2.3 Database & N+1
- Index komposit sudah di doc 03 §10. FULLTEXT untuk `/shop/cari`.
- **Eager loading wajib** (target < 15 query/halaman, PRD §15):
  - `/shop`: `Product::with('primaryImage','collection')`.
  - `/shop/produk/{slug}`: `with('images','reviews.user','collection','category')`.
  - `/shop/pesanan`: `with('items','pembayaran')`.
  - Forum: `with('user','category')->withCount('replies')`.
- Counter (`like_count`, `reply_count`, `total_review`, `rating`) **disimpan kolom** (denormalisasi) + di-maintain Observer → hindari COUNT/AVG real-time.
- Pagination semua listing (jangan `all()`).

### 2.4 Target metrik (PRD §15)
TTFB <500ms · LCP <2.5s desktop/<3.5s mobile · query/halaman <15 · ongkir <3s (timeout 5s) · PDF <3s · mobile initial <1.5MB. Gambar: WebP + lazy + srcset (frontend, di luar scope backend doc ini).

---

## 3. Testing (Pest 4)

### 3.1 Keputusan DB test (BLOKER — putuskan dulu)
> [!CAUTION]
> Doc 03 §11: fitur **MySQL-only** dipakai V2 → `ALTER ... MODIFY ENUM` (migration role), `FULLTEXT` (search), (dan SET jika dipilih — tapi kita pilih `string`, doc 04 §3). SQLite in-memory **tidak** mendukung `ALTER MODIFY ENUM` & `FULLTEXT`.
>
> **Opsi:**
> - **(A) Test pakai MySQL** (disarankan) — `phpunit.xml` arahkan ke DB MySQL test. Semua fitur jalan. Lebih lambat.
> - **(B) SQLite + guard** — bungkus migration MySQL-only dengan `if (DB::getDriverName()==='mysql')`; FULLTEXT search fallback ke `LIKE` saat sqlite. Lebih cepat, tapi test tidak 100% mirror prod.
>
> **Rekomendasi:** (A) untuk CI yang andal; (B) hanya jika kecepatan kritis. Cek `phpunit.xml` existing dulu — V1 mungkin sudah set driver.

### 3.2 Cakupan test (PRD §16 Step 4.4)
**Unit:**
- `PointService` (credit/debit, guard saldo, recalcTier dgn `PointTier::fromPoints`).
- `OngkirService::roundWeight` (min 100, kelipatan 500).
- `PromoService::discount` (persen vs nominal, `diskon_max`).
- `OrderCodeGenerator` (format VYG-S-, unik).
- `ProductOrderState::canTransition` (transisi legal/ilegal).
- Model relations & scopes.

**Feature:**
- Checkout flow: add cart → checkout (ongkir+promo+poin) → order pending + items + stok berkurang + cart kosong.
- Payment: mock Midtrans Snap token; `finish()` & webhook → status paid; **signature mismatch → 403**; idempotency (webhook dobel tidak dobel-proses).
- Empty return: submit → admin approve → poin user bertambah + tier naik + (badge Eco Warrior di threshold).
- Review guard: tidak bisa review sebelum delivered/completed; tidak bisa review dobel.
- Authorization: `admin_store` akses `/admin/store`; customer/owner ditolak; IDOR pesanan user lain → 404/403.
- Invoice PDF (booking & produk) ter-generate (assert content-type/file).
- Free-ongkir: subtotal≥500k → 0; Gold → 0; Silver 1×/bln.

**Mock integrasi:**
- Midtrans: `Http::fake()` tidak cukup (SDK pakai cURL internal) → mock via `Midtrans\*` (wrap di service agar bisa di-mock) atau fake response Transaction::status. Pertimbangkan interface `PaymentGateway` agar testable.
- api.co.id: `Http::fake([...])` untuk `OngkirService` (pakai `Http::` Laravel → mudah di-fake). Test: sukses, timeout→fallback, cache hit.

### 3.3 Contoh skeleton (Pest)
```php
it('places an order and decrements stock', function () {
    $user = User::factory()->customer()->create();
    $product = Product::factory()->create(['stok'=>10,'harga'=>100000,'berat_gram'=>200]);
    actingAs($user);
    app(CartService::class)->add($user, $product, 2);

    $order = app(CheckoutService::class)->place($user, new CheckoutData(addressId: $addr->id_address, kurir:'jne', layanan:'REG', ...));

    expect($order->status)->toBe('pending')
        ->and($order->items)->toHaveCount(1)
        ->and($product->fresh()->stok)->toBe(8);
});

it('rejects webhook with bad signature', function () {
    $res = postJson('/shop/midtrans/webhook', ['order_id'=>'VYG-S-x','status_code'=>'200','gross_amount'=>'1000','signature_key'=>'WRONG']);
    $res->assertStatus(403);
});
```

### 3.4 Factory & Seeder untuk test
- Buat factory: ProductFactory, ProductOrderFactory, dst. (V1 punya UserFactory). Tambah state `customer()`/`adminStore()` di UserFactory.
- `RefreshDatabase` trait. Seeding minimal (ForumCategorySeeder) di setUp bila perlu.

---

## 4. Checklist Kesiapan Backend V2

```
Foundation
  [ ] ALTER users.role + UserRole::ADMIN_STORE
  [ ] 28 migration (urutan kanonik doc 03) — migrate sukses
  [ ] 28 model + update User + Constants + morphMap + Observer
  [ ] composer require barryvdh/laravel-dompdf
  [ ] config/ongkir.php + env vars
  [ ] StorePanelProvider terdaftar + canAccessPanel store
  [ ] Go scraper jalan → FreshProductSeeder + AdminStoreSeeder + ForumCategorySeeder
Core
  [ ] Service layer (CheckoutService, OngkirService, PointService, ProductPaymentService, InvoiceService, ...)
  [ ] FormRequest validasi + anti-manipulasi (weight/harga server-side)
  [ ] Webhook produk + CSRF exception + signature/idempotency/lock
  [ ] State machine status diberlakukan
Cross-module
  [ ] Poin↔checkout, tier↔free-ongkir, tier↔konten, badge↔empty-return/review, forum-tag↔produk
Quality
  [ ] Rate limit, upload validation, XSS sanitize, cache, queue, eager-load
  [ ] Test: unit+feature+authorization+payment+pdf hijau (keputusan DB test §3.1)
```

---

## 5. Ringkasan Item Terbuka Lintas-Dokumen (untuk implementer)
| # | Item | Doc | Keputusan disarankan |
|---|------|-----|----------------------|
| O1 | `skin_type` SET vs string | 03/04 | **string** comma-separated (sudah diselaraskan) |
| O2 | Midtrans webhook: 1 endpoint vs 2 | 06 | tergantung akun; default 2 endpoint, fallback single+dispatch by prefix |
| O3 | Mata uang invoice booking (GBP/IDR) | 06 | samakan dgn UI booking V1 |
| O4 | Kredit poin empty return: Filament action vs Observer | 04/07 | pilih SATU (hindari dobel) |
| O5 | Path panel store `admin/store` vs `/store` | 07 | verifikasi konflik route; fallback `/store` |
| O6 | DB test MySQL vs SQLite | 08 | **MySQL** untuk CI andal |
| O7 | `id_product` onDelete di items/reviews (restrict vs null) | 03 | restrict (jaga histori) |
| O8 | Free-ongkir Silver "1×/bln" cara hitung | 05 | derive dari product_orders bulan berjalan |

---

> [!TIP]
> Dokumentasi teknis V2 (doc 00–08) **selesai**. Saat implementasi, perlakukan ini sebagai *living document*: update bila keputusan O1–O8 difinalisasi atau realita kode berbeda. Selalu mulai dari `00-INDEX.md`.

*Akhir set dokumentasi teknis Architecture & Backend V2.*
