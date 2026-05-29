# 05 — Backend: Controllers, Services & Business Logic V2

> **Tujuan:** Memetakan route → controller → service, mendefinisikan **service layer** (logika bisnis/uang/API), FormRequest, state machine, dan aturan bisnis kritis (checkout, ongkir, poin, promo, payment). Ikuti layering di [`02-arsitektur-overview-v2.md §3`](02-arsitektur-overview-v2.md).
>
> Prinsip: **controller tipis** (validasi+orchestrasi+response), **service tebal** (transaksi DB, kalkulasi, API eksternal).

---

## 1. Daftar Controller V2 & Mapping Route

> Route lengkap ada di PRD §4.4/§5.4/§6.4/§7.4/§8.4. Di sini: method signature + middleware + service yang dipanggil.

### Modul 1 — E-commerce (`/shop`)
| Controller@method | Route | Middleware | Memanggil |
|-------------------|-------|------------|-----------|
| `ShopController@index` | GET /shop | public | Product::featured/active, koleksi, kategori (cache) |
| `ShopController@kategori` | GET /shop/kategori/{slug} | public | filter+sort+paginate |
| `ShopController@koleksi` | GET /shop/koleksi/{slug} | public | — |
| `ShopController@show` | GET /shop/produk/{slug} | public | eager: images,reviews(visible),collection; "You may also like" |
| `ShopController@cari` | GET /shop/cari | public | FULLTEXT search products |
| `SkincareFinderController@form` | GET /shop/skincare-finder | public | — |
| `SkincareFinderController@result` | POST /shop/skincare-finder | public | simpan UserSkincareProfile (jika login) + rekomendasi |
| `WishlistController@index` | GET /shop/wishlist | auth | user->wishlistProducts |
| `WishlistController@toggle` | POST /shop/wishlist/toggle | auth, throttle | toggle row |
| `CartController@index/add/update/remove` | GET/POST/PUT/DELETE /shop/cart* | auth | `CartService` |
| `OngkirController@check` | POST /shop/ongkir/check | auth, throttle:10,1 | `OngkirService` |
| `ProductCheckoutController@index` | GET /shop/checkout | auth, role:customer | tampil alamat+cart+ringkasan |
| `ProductCheckoutController@store` | POST /shop/checkout | auth, role:customer | `CheckoutService::place()` |
| `ProductOrderController@index` | GET /shop/pesanan | auth | user->productOrders |
| `ProductOrderController@show` | GET /shop/order/{kode} | auth (owner check) | detail+resi |
| `ProductOrderController@invoice` | GET /shop/order/{kode}/invoice | auth | `InvoiceService::product()` |
| `ProductPaymentController@show/token/finish` | /shop/order/{kode}/payment* | auth | `ProductPaymentService` |
| `ProductReviewController@store` | POST /shop/produk/{slug}/review | auth, role:customer | guard reviewable + simpan |

### Modul 2–5
| Controller@method | Route | Service |
|-------------------|-------|---------|
| `LookbookController@index/show` | /lookbook, /lookbook/{slug} | view_count++ |
| `LookbookController@shopAll` | POST /lookbook/{slug}/shop-all | `CartService` (semua produk di lookbook) |
| `EmptyReturnController@index/create/store/history` | /empty-return* | simpan EmptyReturn+photos |
| `PointController@index/history` | /akun/poin* | user->points, point_transactions |
| `ExclusiveContentController@index/show` | /eksklusif* | `ExclusiveContent::forTier(user->tier())` |
| `ForumController@index/kategori/show` | /komunitas* | thread list/detail |
| `ForumController@create/store` | /komunitas/thread* | `ForumService` (slug, +poin) |
| `ForumReplyController@store` | POST /komunitas/thread/{slug}/reply | nested guard ≤2 level |
| `ForumInteractionController@likeThread/likeReply/bookmark` | POST … | `ForumInteractionService` |
| `ForumController@leaderboard` | GET /komunitas/leaderboard | community_points top-10 (cache) |
| `AkunController@bookingDetail` | GET /akun/bookings/{kode} | (update controller V1) |
| `AkunController@downloadInvoice` | GET /akun/bookings/{kode}/invoice | `InvoiceService::booking()` |

> **Owner check** pada `/shop/order/{kode}`: query `where('id_user', auth()->id())->where('kode_order',$kode)->firstOrFail()` (pola V1, cegah IDOR). Sama untuk invoice & payment.

---

## 2. Service Layer

### 2.1 `OrderCodeGenerator`
```php
class OrderCodeGenerator {
    /** Format: VYG-S-YYMMDD-XXXX (X = base36 acak), dijamin unik. */
    public function shopOrder(): string {
        do { $code='VYG-S-'.now()->format('ymd').'-'.strtoupper(Str::random(4)); }
        while (ProductOrder::where('kode_order',$code)->exists());
        return $code;
    }
}
```
> V1 pakai `VYG-`+random8. Produk WAJIB prefix `VYG-S-` (lihat doc 01 §5). Webhook produk strip suffix retry `-R\d+` sama seperti V1.

### 2.2 `CartService`
```php
class CartService {
    public function add(User $u, Product $p, int $qty): Cart;       // upsert (unique id_user+id_product), validasi stok
    public function update(User $u, int $idCart, int $qty): void;   // qty>=1, <= stok
    public function remove(User $u, int $idCart): void;
    public function items(User $u): Collection;                     // with('product.primaryImage')
    public function subtotal(User $u): float;                       // Σ hargaEfektif*qty
    public function totalWeight(User $u): int;                      // Σ berat_gram*qty, min 100, bulat ke atas /500
    public function clear(User $u): void;
}
```

### 2.3 `OngkirService` (api.co.id) — detail integrasi di doc 06
```php
class OngkirService {
    /** Cache 1 jam per (origin,destination,weight). Timeout 5s → fallback error. */
    public function cost(string $destination, int $weight): array;  // list kurir+layanan+tarif+etd
    public function roundWeight(int $grams): int;                   // min 100, bulat ke atas kelipatan 500
}
```

### 2.4 `PromoService` (reuse `Promo` V1)
```php
class PromoService {
    /** Validasi kode utk subtotal tertentu. Throw/false jika invalid. */
    public function validate(string $kode, float $subtotal): ?Promo;   // scopeActive + min_transaksi + stock>0
    public function discount(Promo $promo, float $subtotal): float;    // hormati diskon_max & tipe_promo (persen/nominal)
}
```
> Reuse field V1: `tipe_promo`, `diskon`, `diskon_max`, `min_transaksi`, `stock`, `used_counter`, `status`, `time_expired`. Increment `used_counter` saat dipakai (dalam transaksi checkout).

### 2.5 `PointService` (Empty Return ↔ Checkout)
```php
class PointService {
    const RUPIAH_PER_POINT = 1000;
    public function credit(User $u, int $amount, string $source, ?Model $ref=null, string $desc=''): void; // earn
    public function debit(User $u, int $amount, string $source, ?Model $ref=null, string $desc=''): void;   // spend, guard saldo
    public function rupiahValue(int $points): float;          // points * 1000
    public function recalcTier(User $u): void;                // PointTier::fromPoints(total_earned) → user_points.tier
}
```
**Aturan:** semua mutasi saldo dalam `DB::transaction` + `lockForUpdate` pada `user_points`. Setiap mutasi tulis `point_transactions` (type, amount, source, reference morph, saldo_after). `debit` gagal jika `saldo < amount`. `credit` panggil `recalcTier` setelah update.

### 2.6 `CheckoutService` — JANTUNG Modul 1
```php
class CheckoutService {
    public function place(User $u, CheckoutData $d): ProductOrder
    {
        return DB::transaction(function () use ($u,$d) {
            $items = $this->cart->items($u);                  // lock produk untuk cek stok
            abort_if($items->isEmpty(), 422, 'Cart kosong');

            $subtotal = 0; $weight = 0; $lines = [];
            foreach ($items as $it) {
                $p = Product::where('id_product',$it->id_product)->lockForUpdate()->firstOrFail();
                abort_if($p->stok < $it->qty, 422, "Stok {$p->nama} kurang");
                $harga = (float) ($p->harga_diskon ?? $p->harga);
                $sub   = $harga * $it->qty;
                $subtotal += $sub; $weight += $p->berat_gram * $it->qty;
                $lines[] = compact('p','it','harga','sub');
            }

            // Ongkir (free jika subtotal>=threshold ATAU tier Silver(1x/bln)/Gold(unlimited))
            $biayaKirim = $this->resolveShipping($u, $subtotal, $d);

            // Promo (opsional)
            $diskon = $d->promo ? $this->promo->discount($d->promo, $subtotal) : 0;

            // Poin (opsional) — debit saldo
            $potonganPoin = 0;
            if ($d->poin > 0) {
                $this->point->debit($u, $d->poin, 'purchase_discount', null, 'Checkout');
                $potonganPoin = $this->point->rupiahValue($d->poin);
            }

            $grand = max(0, $subtotal + $biayaKirim - $diskon - $potonganPoin);

            $order = ProductOrder::create([
                'id_user'=>$u->id_user, 'id_address'=>$d->addressId, 'id_promo'=>$d->promo?->id_promo,
                'kode_order'=>$this->codeGen->shopOrder(),
                'subtotal'=>$subtotal,'biaya_kirim'=>$biayaKirim,'total_diskon'=>$diskon,
                'poin_digunakan'=>$d->poin,'potongan_poin'=>$potonganPoin,'grand_total'=>$grand,
                'kurir'=>$d->kurir,'layanan_kirim'=>$d->layanan,'estimasi_tiba'=>$d->etd,
                'status'=>ProductOrderStatus::PENDING,
            ]);

            foreach ($lines as $l) {
                $order->items()->create([
                    'id_product'=>$l['p']->id_product,'nama_produk'=>$l['p']->nama,
                    'qty'=>$l['it']->qty,'harga_satuan'=>$l['harga'],
                    'berat_gram'=>$l['p']->berat_gram,'subtotal'=>$l['sub'],
                ]);
                $l['p']->decrement('stok', $l['it']->qty);   // kurangi stok saat order dibuat
            }
            if ($d->promo) $d->promo->increment('used_counter');
            $this->cart->clear($u);
            return $order;
        });
    }
}
```
> **Keputusan stok:** kurangi stok saat order **dibuat** (pending), bukan saat paid — cegah oversell. **Konsekuensi:** order `pending` yang tak dibayar harus di-cancel oleh scheduler (kembalikan stok + refund poin). Lihat §5.

### 2.7 `ProductPaymentService` (Midtrans — pola V1, TANPA konversi GBP)
Replikasi teknik `PaymentController` V1 (doc 01 §6) tapi:
- `gross_amount = (int) round($order->grand_total)` — **IDR langsung, tanpa `convertGbpToIdr`**.
- `item_details` dari `product_order_items` (`harga_satuan`*qty) + baris ongkir + baris diskon/poin (negatif) agar Σ = grand_total (Midtrans menolak mismatch).
- Simpan ke `product_pembayaran` (kolom `status`, `midtrans_order_id`, `snap_token`, `midtrans_transaction_id`, `raw_response`, `paid_at`).
- `finish()` & `webhook()`: verifikasi SHA512, idempotency guard, `lockForUpdate`, mapping status. Pada `settlement/capture` → `product_pembayaran.status=success` + `product_orders.status=paid`.
- Webhook produk butuh route + **CSRF exception** baru di `bootstrap/app.php` (mis. `shop/midtrans/webhook`). Detail di doc 06.

### 2.8 `InvoiceService` (DomPDF — doc 06)
```php
class InvoiceService {
    public function booking(Order $o): \Barryvdh\DomPDF\PDF;   // resources/views/pdf/invoice-booking.blade.php
    public function product(ProductOrder $o): \Barryvdh\DomPDF\PDF; // invoice-product.blade.php
}
// Controller: return $service->booking($o)->stream("VIYGO-Invoice-{$o->kode_order}.pdf");
```

### 2.9 Community services
- `ForumService::createThread(User,data)` — generate slug unik, simpan, `CommunityPointService::add(CREATE_THREAD)`, sync tag produk.
- `ForumInteractionService` — toggle like (unique guard), bookmark, update counter via `increment/decrement`, +poin ke author.
- `BadgeService::evaluate(User)` — assign badge bila threshold tercapai (Eco Warrior ≥5 empty_return approved, Top Reviewer ≥10 review, Skincare Guru ≥20 tip-thread, Rising Star ≥50 community points). Idempotent (unique id_user+badge_slug).

---

## 3. FormRequest (validasi)

| Request | Aturan inti |
|---------|-------------|
| `CartAddRequest` | id_product exists & status active; qty 1..stok |
| `CheckoutRequest` | id_address ada & milik user; kurir∈config couriers; layanan_kirim required; promo_code nullable string; poin nullable int ≤ saldo user; cart tidak kosong |
| `OngkirCheckRequest` | destination required string; weight derived server-side dari cart (jangan percaya weight dari client) |
| `SubmitProductReviewRequest` | rating 1..5; komentar nullable; foto[] max 3, mime jpg/png/webp, ≤2MB; guard: order delivered/completed & milik user & belum review |
| `EmptyReturnRequest` | jumlah ≥1; metode∈dropoff/pickup; jika dropoff → id_salon ada; foto[] 1..3 ≤2MB; product nullable |
| `ThreadRequest` | judul required; id_forum_category exists; konten required (HTMLPurifier untuk rich text); tag produk[] nullable |
| `ReplyRequest` | konten required; parent_id nullable & milik thread & bukan reply-of-reply (≤2 level) |

> **Penting:** `OngkirCheckRequest`/`CheckoutRequest` — berat & subtotal **dihitung server dari cart**, bukan dari input client (cegah manipulasi ongkir/harga). Hanya `destination`, `kurir`, `layanan`, `promo`, `poin` yang dari client.

---

## 4. State Machines (guard transisi)

Sediakan helper transisi agar status tidak loncat ilegal. Contoh `product_orders`:

```php
class ProductOrderState {
    private const NEXT = [
        'pending'    => ['paid','cancelled'],
        'paid'       => ['processing','refunded'],
        'processing' => ['shipped','refunded'],
        'shipped'    => ['delivered'],
        'delivered'  => ['completed'],
    ];
    public static function canTransition(string $from,string $to): bool {
        return in_array($to, self::NEXT[$from] ?? [], true);
    }
}
```
- **Customer** boleh: pending→cancelled (batal sebelum bayar). 
- **Admin Store** (Filament) boleh: paid→processing→shipped(+resi)→ (delivered/completed bisa otomatis/scheduler).
- **Sistem (payment)**: pending→paid (webhook).
- **Refund**: paid/processing→refunded (Midtrans refund, pola `BookingController::batal` V1 yang pakai `Transaction::refund` dengan `refund_key` unik).

State machine lain: `empty_returns` (pending→approved/rejected; approved→picked_up→received), `product_pembayaran` (pending→success/failed/expired/refund).

---

## 5. Aturan Bisnis Kritis

### 5.1 Free ongkir & tier (`resolveShipping`)
```
if subtotal >= config('ongkir.free_ongkir_threshold' = 500000) → biaya=0
elseif user tier == GOLD → biaya=0 (unlimited)
elseif user tier == SILVER and (pemakaian free-ongkir bulan ini < 1) → biaya=0, catat pemakaian
else → biaya = tarif kurir terpilih (dari OngkirService, divalidasi ulang server-side)
```
> "Pemakaian free-ongkir bulan ini" untuk Silver: hitung dari `product_orders` user bulan berjalan yang `biaya_kirim=0` & subtotal<threshold & tier silver. Simpan penanda agar tidak bisa diakali.

### 5.2 Poin
- 1 poin = Rp 1.000 (`PointService::RUPIAH_PER_POINT`).
- Debit saat checkout (bukan saat paid). **Jika order cancelled/expired → refund poin** (credit balik) via scheduler/observer.
- Poin earn dari Empty Return hanya saat status→`approved`.

### 5.3 Review guard
- Hanya jika ada `product_order_items` dengan produk tsb di order milik user berstatus delivered/completed, dan belum ada review untuk (user,product,order). Set `is_verified_purchase=true`.

### 5.4 Stok & oversell
- Kurangi stok saat order dibuat (lock produk). Jika produk `stok=0` → set `status='out_of_stock'` (observer/Product saving). Kembalikan stok saat cancel/refund.

### 5.5 Scheduler (pola `CompleteBookings` V1)
- `CancelStaleProductOrders` — cancel `product_orders` pending > N jam tak dibayar: kembalikan stok, refund poin, set cancelled. Daftar di `routes/console.php` / `Schedule`.
- (opsional) auto `shipped→delivered→completed` setelah X hari (atau manual oleh admin).

---

## 6. Reuse & perbedaan dari V1 (ringkas)

| Aspek | V1 | V2 produk |
|-------|----|-----------|
| Kode order | `VYG-`+rand8 (inline) | `VYG-S-`+date+rand via `OrderCodeGenerator` |
| Uang | GBP → convert IDR saat bayar | IDR murni, no convert |
| Payment controller | `PaymentController` (Order salon) | `ProductPaymentController` + `ProductPaymentService` (terpisah) |
| Webhook | `/midtrans/webhook` (CSRF except) | route+CSRF except baru utk produk |
| Discount | promo di BookingController | `PromoService` (reuse model Promo) |
| Side-effect rating | `ReviewObserver` | `ProductReviewObserver` |
| Auto-complete | `CompleteBookings` command | `CancelStaleProductOrders` (+opsional complete) |

---

## 7. Verifikasi (acceptance Phase 2)
```bash
php artisan route:list | grep shop                      # semua route shop terdaftar
# E2E manual: add to cart → checkout (ongkir+promo+poin) → snap token → finish → status paid
php artisan test --filter=Checkout                      # feature test (doc 08)
```

---

*Berikutnya: `06-integrasi.md` — Midtrans (produk), api.co.id ongkir, DomPDF, Go scraper + seeder, env & config.*
