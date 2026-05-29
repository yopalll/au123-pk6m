# 06 — Integrasi Eksternal V2

> **Tujuan:** Spesifikasi konkret 4 integrasi: **Midtrans** (pembayaran produk), **api.co.id** (ongkir), **DomPDF** (invoice), **Go scraper + Seeder** (data produk fresh.com). Termasuk env vars, config, dan acuan kode V1.

---

## 1. Midtrans — Pembayaran Produk

### 1.1 Status
- SDK `midtrans/midtrans-php ^2.6` **sudah terinstall** & dikonfigurasi di `config/services.php` (`services.midtrans.*`). Reuse config yang sama.
- Pola lengkap ada di `PaymentController` V1 — **pelajari doc 01 §6**. `ProductPaymentService` (doc 05 §2.7) meniru pola tsb.

### 1.2 Perbedaan wajib vs V1
| Hal | V1 (salon) | Produk V2 |
|-----|------------|-----------|
| Mata uang | GBP → `convertGbpToIdr()` (rate 20000) | **IDR murni** — `gross_amount=(int) round($order->grand_total)`, **tanpa konversi** |
| Tabel | `pembayaran` (`status_pembayaran`) | `product_pembayaran` (`status`) |
| order_id Midtrans | `order.kode_order` (VYG-) | `product_orders.kode_order` (VYG-S-) |
| item_details | dari `order.details` (service) | dari `product_order_items` + baris ongkir + baris diskon/poin (negatif) |
| Webhook route | `/midtrans/webhook` | route baru (lihat 1.4) |

> [!IMPORTANT]
> **gross_amount harus = Σ item_details.** Susun item: tiap produk (`harga_satuan`×qty), lalu `+ biaya_kirim`, lalu `- total_diskon`, `- potongan_poin`. Midtrans menolak `gross_amount` yang tidak sama dengan jumlah item.

### 1.3 Snap token (produk)
```php
MidtransConfig::$serverKey = config('services.midtrans.server_key');
MidtransConfig::$isProduction = config('services.midtrans.is_production');
// ...is_sanitized, is_3ds (sama spt PaymentController)

$params = [
  'transaction_details' => ['order_id'=>$order->kode_order, 'gross_amount'=>(int) round($order->grand_total)],
  'customer_details'    => [...first_name,last_name,email,phone dari $order->user],
  'item_details'        => $this->buildItems($order),   // Σ == grand_total
];
$snap = Snap::getSnapToken($params);
// simpan product_pembayaran: snap_token, midtrans_order_id=$order->kode_order, status=pending, jumlah=grand_total
```
Retry order_id conflict: append `-R{time}` (pola V1). Webhook strip `/-R\d+$/`.

### 1.4 Webhook produk
Route baru di `routes/web.php`:
```php
Route::post('/shop/midtrans/webhook', [ProductPaymentController::class, 'webhook'])
    ->middleware('throttle:120,1')->name('shop.midtrans.webhook');
```
Tambah CSRF exception di [`bootstrap/app.php`](../../bootstrap/app.php):
```php
$middleware->validateCsrfTokens(except: [
    'midtrans/webhook',          // V1 (sudah ada)
    'shop/midtrans/webhook',     // V2 produk (TAMBAH)
]);
```
> **Konfigurasi dashboard Midtrans:** webhook salon & produk pakai URL berbeda. Karena Midtrans biasanya 1 Payment Notification URL per merchant, **alternatif yang lebih aman**: gunakan **satu** webhook endpoint dan **route berdasar prefix `kode_order`** (`VYG-S-` → produk, `VYG-` → salon). Putuskan saat implementasi; doc ini menyediakan endpoint terpisah sebagai default, tapi single-endpoint+dispatch lebih praktis untuk 1 akun Midtrans.

Webhook produk WAJIB: verifikasi SHA512 (`hash('sha512', order_id.status_code.gross_amount.server_key)`), idempotency guard (`midtrans_transaction_id` + status success), `lockForUpdate` di `DB::transaction`, mapping status → `product_pembayaran.status` + `product_orders.status=paid` saat settlement/capture.

### 1.5 Refund (pembatalan)
Pola `BookingController::batal` V1: `Transaction::refund($txnId, ['refund_key'=>'refund_'.$kode.'_'.now()->format('YmdHis'), 'amount'=>..., 'reason'=>...])`. `refund_key` unik (Midtrans tolak duplikat HTTP 412). Set `product_pembayaran.status=refund`, `product_orders.status=refunded`, kembalikan stok + refund poin.

---

## 2. api.co.id — Ongkir Real-time

### 2.1 Config baru `config/ongkir.php`
```php
return [
    'api_key'   => env('API_CO_ID_KEY'),
    'base_url'  => env('API_CO_ID_BASE_URL', 'https://api.co.id'),
    'origin_city' => env('ONGKIR_ORIGIN_CITY', 'Jakarta Selatan'),  // warehouse VIYGO
    'free_ongkir_threshold' => (int) env('ONGKIR_FREE_THRESHOLD', 500000),
    'couriers'  => ['jne','jnt','sicepat','pos'],
    'timeout'   => 5,        // detik
    'cache_ttl' => 3600,     // 1 jam
];
```

### 2.2 Env vars baru (`.env.example`)
```
API_CO_ID_KEY=
API_CO_ID_BASE_URL=https://api.co.id
ONGKIR_ORIGIN_CITY="Jakarta Selatan"
ONGKIR_FREE_THRESHOLD=500000
```

### 2.3 Endpoint
```
POST {base_url}/expedition/cost
Header: x-api-co-id: {api_key}, Content-Type: application/json
Body: { origin, destination, weight(gram), courier:"jne,jnt,sicepat,pos" }
```
Response: `data[]` per courier → `services[]` (service, description, cost, etd). Lihat PRD §11.3.

### 2.4 `OngkirService` (implementasi)
```php
class OngkirService {
    public function cost(string $destination, int $weight): array {
        $weight = $this->roundWeight($weight);
        $key = 'ongkir:'.md5(config('ongkir.origin_city').'|'.$destination.'|'.$weight);
        return Cache::remember($key, config('ongkir.cache_ttl'), function () use ($destination,$weight) {
            $res = Http::withHeaders(['x-api-co-id'=>config('ongkir.api_key')])
                ->timeout(config('ongkir.timeout'))
                ->post(config('ongkir.base_url').'/expedition/cost', [
                    'origin'=>config('ongkir.origin_city'),
                    'destination'=>$destination,
                    'weight'=>$weight,
                    'courier'=>implode(',', config('ongkir.couriers')),
                ]);
            abort_unless($res->successful(), 502, 'Gagal cek ongkir. Coba lagi.');
            return $res->json('data', []);
        });
    }
    public function roundWeight(int $g): int {        // min 100, bulat ke atas kelipatan 500
        $g = max(100, $g);
        return (int) (ceil($g / 500) * 500);
    }
}
```
> **Keamanan:** controller TIDAK menerima `weight` dari client — hitung dari cart (`CartService::totalWeight`). `destination` dari input/alamat. **Validasi server** tarif yang dipilih saat checkout (jangan percaya tarif yang dikirim balik client) — re-fetch & cocokkan kurir+layanan.
> **Caching:** kunci per (origin,destination,weight). TTL 1 jam (PRD §15). **Timeout 5s → fallback** (pesan error, jangan blokir checkout selamanya). Rate limit route `throttle:10,1`.

---

## 3. DomPDF — Invoice PDF

### 3.1 Install (Phase 1)
```bash
composer require barryvdh/laravel-dompdf
```
> ⚠️ **Belum terinstall** (doc 00 koreksi C6). Auto-discovery Laravel → facade `Barryvdh\DomPDF\Facade\Pdf` tersedia tanpa config tambahan.

### 3.2 InvoiceService + template
```php
use Barryvdh\DomPDF\Facade\Pdf;
class InvoiceService {
    public function booking(Order $o) {
        $o->loadMissing(['user','salon','details.service','details.staff','pembayaran']);
        return Pdf::loadView('pdf.invoice-booking', ['order'=>$o])->setPaper('a4');
    }
    public function product(ProductOrder $o) {
        $o->loadMissing(['user','address','items','pembayaran']);
        return Pdf::loadView('pdf.invoice-product', ['order'=>$o])->setPaper('a4');
    }
}
```
Controller stream (tidak disimpan):
```php
return $invoice->booking($order)->stream("VIYGO-Invoice-{$order->kode_order}.pdf");
```
Template: `resources/views/pdf/invoice-booking.blade.php`, `invoice-product.blade.php`. Konten per PRD §8.3.2 (header logo, info pelanggan/salon, tabel item, ringkasan, info pembayaran, footer).

> **Catatan harga di invoice booking:** harga V1 dalam GBP. Tentukan apakah invoice menampilkan GBP, IDR (×rate), atau keduanya. Konsisten dengan tampilan UI booking. Invoice produk murni IDR.
> **Performa:** PDF < 3s (PRD §15). Untuk volume tinggi, generate via Queue (doc 08). Untuk on-demand stream, sinkron OK.

---

## 4. Go Scraper + Seeder — Data Produk fresh.com

### 4.1 Tujuan & sifat
Scrape sekali (offline) untuk **populasi data demo**, bukan service runtime. Output JSON → di-seed via Laravel. Lihat PRD §9.

### 4.2 Struktur
```
scripts/scraper/
├── fresh_scraper.go        # chromedp (render JS) + goquery (parse DOM)
├── go.mod / go.sum
├── config.json             # base_url, collections[], usd_to_idr_rate, output_dir, image_dir, delay_ms
└── output/                 # JSON per kategori
public/images/products/fresh/   # gambar ter-download
```
`config.json` & arsitektur fungsi (`main`, `scrapeProductList`, `scrapeProductDetail`, `parseProductData`, `convertPrice`, `downloadImage`, `exportToJSON`) — ikut PRD §9.4. `usd_to_idr_rate` (default 16200) untuk konversi USD→IDR (catatan: fresh.com USD; ini beda dari exchange_rate GBP Midtrans V1).

### 4.3 Output JSON (kontrak dengan seeder)
Tiap objek: `fresh_product_id, fresh_url, nama, kategori, koleksi, deskripsi, key_ingredients, full_ingredients, cara_pemakaian, harga_usd, harga_idr, volume_ml, berat_gram, skin_type, skin_concern, badge, images[]`. (PRD §9.4 contoh.)

### 4.4 `FreshProductSeeder` (idempotent)
```php
class FreshProductSeeder extends Seeder {
    public function run(): void {
        foreach (glob(base_path('scripts/scraper/output/*.json')) as $file) {
            foreach (json_decode(file_get_contents($file), true) as $row) {
                $cat = ProductCategory::firstOrCreate(['slug'=>Str::slug($row['kategori'])], ['nama'=>$row['kategori']]);
                $col = $row['koleksi'] ? ProductCollection::firstOrCreate(['slug'=>Str::slug($row['koleksi'])],['nama'=>$row['koleksi']]) : null;
                $p = Product::updateOrCreate(
                    ['fresh_product_id'=>$row['fresh_product_id']],    // unique key → idempotent
                    [
                      'id_product_category'=>$cat->id_product_category,
                      'id_collection'=>$col?->id_collection,
                      'nama'=>$row['nama'], 'slug'=>Str::slug($row['nama']),
                      'deskripsi'=>$row['deskripsi'], 'key_ingredients'=>$row['key_ingredients'],
                      'full_ingredients'=>$row['full_ingredients'], 'cara_pemakaian'=>$row['cara_pemakaian'],
                      'harga'=>$row['harga_idr'], 'berat_gram'=>$row['berat_gram'], 'volume_ml'=>$row['volume_ml'],
                      'skin_type'=>$row['skin_type'], 'skin_concern'=>$row['skin_concern'],
                      'badge'=>$row['badge'] ?? null, 'fresh_url'=>$row['fresh_url'], 'status'=>'active',
                      'stok'=> $row['stok'] ?? 100,
                    ]
                );
                $p->images()->delete();
                foreach ($row['images'] as $i=>$url) {
                    $p->images()->create(['image_url'=>$url, 'is_primary'=>$i===0, 'sort_order'=>$i]);
                }
            }
        }
    }
}
```
> Idempotent via `updateOrCreate(['fresh_product_id'=>…])` (butuh unique di doc 03). Slug bisa bentrok → tambahkan suffix bila perlu.

### 4.5 `AdminStoreSeeder` (hati-hati mass-assignment)
```php
class AdminStoreSeeder extends Seeder {
    public function run(): void {
        $u = User::firstOrNew(['email'=>'admin.store@viygo.id']);
        $u->fill(['first_name'=>'Admin','last_name'=>'Store VIYGO',
                  'password'=>Hash::make(env('ADMIN_STORE_PASSWORD','ViygoStore2026!'))]);
        $u->role = UserRole::ADMIN_STORE;     // ⚠️ property assignment — role di $guarded
        $u->is_active = true;
        $u->email_verified_at = now();
        $u->save();
    }
}
```
> ⚠️ `role`/`is_active` ada di `$guarded` (doc 01 §3.2) → **wajib** set via property, bukan mass-assign. Pakai `first_name`/`last_name`, bukan `name`. Ganti password default via env setelah deploy.

### 4.6 `ForumCategorySeeder`
Seed 5 kategori: Review Produk, Tips Skincare, Routine & Lifestyle, Peduli Lingkungan, Diskusi Umum (PRD §7.3.1). `firstOrCreate(['slug'=>…])`.

### 4.7 Menjalankan
```bash
cd scripts/scraper && go run fresh_scraper.go      # hasilkan JSON + download gambar
php artisan db:seed --class=FreshProductSeeder
php artisan db:seed --class=AdminStoreSeeder
php artisan db:seed --class=ForumCategorySeeder
```
Daftarkan di `DatabaseSeeder::run()` bila ingin ikut `db:seed` global (urut: setelah seeder V1).

---

## 5. Ringkasan Env Vars Baru
```
# Ongkir (api.co.id)
API_CO_ID_KEY=
API_CO_ID_BASE_URL=https://api.co.id
ONGKIR_ORIGIN_CITY="Jakarta Selatan"
ONGKIR_FREE_THRESHOLD=500000
# Admin Store
ADMIN_STORE_PASSWORD=ViygoStore2026!
# (Midtrans sudah ada di V1: MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, MIDTRANS_IS_PRODUCTION, MIDTRANS_EXCHANGE_RATE)
```

## 6. Verifikasi
```bash
php artisan tinker --execute="dump(config('ongkir.api_key') !== null, class_exists(Barryvdh\DomPDF\Facade\Pdf::class));"
php artisan db:seed --class=FreshProductSeeder && php artisan tinker --execute="echo App\Models\Product::count();"
# webhook produk: kirim payload uji bertanda tangan SHA512 valid → status order jadi paid
```

---

*Berikutnya: `07-admin-store-filament.md` — StorePanelProvider, Resources, widget, authorization (Filament v5).*
