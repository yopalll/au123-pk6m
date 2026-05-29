# 03 — Database Schema & Migrations V2

> **Tujuan:** Spesifikasi lengkap **1 ALTER + 28 CREATE migration** untuk V2: kolom, tipe, FK, index, urutan eksekusi. Setiap tabel disertai blueprint Laravel siap pakai. Ikuti konvensi di [`00-INDEX.md §2`](00-INDEX.md#2-konvensi-global-wajib-diikuti-semua-modul-v2).
>
> Acuan: PRD §4.4, §5.4, §6.4, §7.4, §12. **Koreksi terhadap PRD ada di kotak ⚠️ dan tertandai.**

---

## 0. Konvensi migration V2 (recap + tambahan)

| Aturan | Detail |
|--------|--------|
| Primary key | `$table->id('id_<nama>')` untuk tabel domain. Tabel pivot/kecil boleh `$table->id()`. |
| FK ke tabel V1 | `->constrained('users','id_user')` / `('promo','id_promo')` / `('salon','id_salon')` — **wajib sebut kolom PK**. |
| FK ke tabel V2 | `->constrained('products','id_product')` dst. (PK custom juga). |
| onDelete | `cascadeOnDelete()` untuk anak yang ikut induk; `nullOnDelete()` untuk FK opsional (promo, salon drop-off). |
| Uang | `decimal(12,2)` (ikut V1; **bukan** 10,2 seperti PRD). |
| Berat | `unsignedInteger` (gram). |
| Timestamp | `$table->timestamps()` kecuali dinyatakan hanya `created_at`. |
| Boolean default | sebutkan eksplisit (`->default(false)`). |
| Charset | ikut default app (utf8mb4) — penting untuk emoji di forum/konten. |
| Penamaan file | `YYYY_MM_DD_NNNNNN_<deskripsi>.php`, prefix tanggal ≥ `2026_06_01` agar jalan setelah migrasi V1. |

> [!NOTE]
> Contoh penanggalan file ikut PRD: `2026_06_01_000001_...`. Karena migration dijalankan urut nama file, **nomor urut (`000001`…`000029`) menentukan urutan** — pakai urutan kanonik di §2.

---

## 1. Migration #0 — ALTER `users.role` (WAJIB pertama)

**File:** `2026_06_01_000001_add_admin_store_role_to_users.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `users` MODIFY `role` "
            . "ENUM('customer','salon_owner','admin','admin_store') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }

    public function down(): void
    {
        // Jangan rollback jika masih ada user admin_store (akan error truncation).
        DB::statement(
            "ALTER TABLE `users` MODIFY `role` "
            . "ENUM('customer','salon_owner','admin') "
            . "NOT NULL DEFAULT 'customer'"
        );
    }
};
```

> Setelah ini, update juga `App\Constants\UserRole` (tambah `ADMIN_STORE='admin_store'` + di `all()`). Lihat doc 04 §User.
> Verifikasi: `SHOW COLUMNS FROM users LIKE 'role';` → enum 4 nilai.

---

## 2. Urutan Kanonik 28 Migration (induk → anak)

> [!CAUTION]
> **Koreksi urutan PRD:** PRD §18 Step 1.2 menaruh `product_reviews` (No.14) di Batch C dengan FK ke `product_orders` (No.16) yang belum dibuat ("[deferred]"). **Solusi yang dipakai di sini: pindahkan `product_reviews` ke SETELAH `product_orders`** (Batch D). Ini menghilangkan kebutuhan deferred FK. Urutan kanonik final:

| Urut | Tabel | Batch | FK keluar |
|------|-------|-------|-----------|
| 02 | product_categories | A | self `parent_id`(null) |
| 03 | product_collections | A | — |
| 04 | user_addresses | A | users |
| 05 | user_skincare_profiles | A | users |
| 06 | user_points | A | users |
| 07 | community_points | A | users |
| 08 | forum_categories | A | — |
| 09 | exclusive_contents | A | — |
| 10 | lookbooks | A | — |
| 11 | products | B | product_categories, product_collections |
| 12 | product_images | C | products |
| 13 | wishlists | C | users, products |
| 14 | carts | C | users, products |
| 15 | lookbook_slides | C | lookbooks |
| 16 | product_orders | D | users, user_addresses, promo(V1) |
| 17 | product_order_items | D | product_orders, products |
| 18 | product_pembayaran | D | product_orders, users |
| 19 | **product_reviews** | D | users, products, product_orders |
| 20 | empty_returns | E | users, products(null), salon(V1,null) |
| 21 | empty_return_photos | E | empty_returns |
| 22 | point_transactions | E | users |
| 23 | forum_threads | F | users, forum_categories |
| 24 | forum_replies | F | forum_threads, users, self parent |
| 25 | forum_likes | F | users (polymorphic) |
| 26 | forum_bookmarks | F | users, forum_threads |
| 27 | forum_thread_tags | F | forum_threads, products |
| 28 | user_badges | F | users |
| 29 | lookbook_items | G | lookbook_slides, products |

> Total = **1 ALTER + 28 CREATE = 29 migration**. (Tabel = 28; `product_reviews` dipindah, jumlah tetap.)

---

## 3. Batch A — Tabel induk (tanpa FK ke tabel V2 lain)

### product_categories
```php
Schema::create('product_categories', function (Blueprint $t) {
    $t->id('id_product_category');
    $t->string('nama');
    $t->string('slug')->unique();
    $t->text('deskripsi')->nullable();
    $t->string('icon_url')->nullable();
    $t->foreignId('parent_id')->nullable()
      ->constrained('product_categories', 'id_product_category')->nullOnDelete();
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
    $t->index('parent_id');
});
```

### product_collections
```php
Schema::create('product_collections', function (Blueprint $t) {
    $t->id('id_collection');
    $t->string('nama');               // "Black Tea", "Rose", "Soy"
    $t->string('slug')->unique();
    $t->text('deskripsi')->nullable();
    $t->string('banner_url')->nullable();
    $t->string('tagline')->nullable();
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
});
```

### user_addresses
```php
Schema::create('user_addresses', function (Blueprint $t) {
    $t->id('id_address');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->string('label')->nullable();          // "Rumah", "Kantor"
    $t->string('nama_penerima');
    $t->string('phone', 20);
    $t->text('alamat_lengkap');
    $t->string('kota');                        // nama kota tujuan (dari dropdown api.co.id)
    $t->string('kota_id')->nullable();         // ID kota api.co.id → request ongkir
    $t->string('provinsi')->nullable();
    $t->string('provinsi_id')->nullable();
    $t->string('kode_pos', 10)->nullable();
    $t->boolean('is_default')->default(false);
    $t->timestamps();
    $t->index(['id_user', 'is_default']);
});
```
> ⚠️ TIDAK FK ke tabel `kota` V1. Domain berbeda (PRD §4.4 catatan).

### user_skincare_profiles
```php
Schema::create('user_skincare_profiles', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->unique()->constrained('users', 'id_user')->cascadeOnDelete();
    $t->enum('skin_type', ['oily','dry','combination','sensitive','normal'])->nullable();
    $t->string('skin_concerns')->nullable();   // comma-separated
    $t->timestamps();   // PRD hanya updated_at; pakai timestamps() lebih aman
});
```

### user_points
```php
Schema::create('user_points', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->unique()->constrained('users', 'id_user')->cascadeOnDelete();
    $t->integer('saldo')->default(0);
    $t->integer('total_earned')->default(0);
    $t->integer('total_spent')->default(0);
    $t->enum('tier', ['starter','bronze','silver','gold'])->default('starter');
    $t->timestamps();
});
```

### community_points
```php
Schema::create('community_points', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->unique()->constrained('users', 'id_user')->cascadeOnDelete();
    $t->integer('total_points')->default(0);
    $t->timestamps();
});
```

### forum_categories
```php
Schema::create('forum_categories', function (Blueprint $t) {
    $t->id('id_forum_category');
    $t->string('nama');
    $t->string('slug')->unique();
    $t->text('deskripsi')->nullable();
    $t->string('icon')->nullable();
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
});
```

### exclusive_contents
```php
Schema::create('exclusive_contents', function (Blueprint $t) {
    $t->id('id_content');
    $t->string('judul');
    $t->string('slug')->unique();
    $t->enum('tipe', ['article','video','tip']);
    $t->longText('konten')->nullable();        // article/tip
    $t->string('video_url')->nullable();
    $t->string('thumbnail_url')->nullable();
    $t->enum('min_tier', ['bronze','silver','gold']);
    $t->boolean('is_published')->default(false);
    $t->timestamps();
    $t->index(['is_published', 'min_tier']);
});
```

### lookbooks
```php
Schema::create('lookbooks', function (Blueprint $t) {
    $t->id('id_lookbook');
    $t->string('judul');
    $t->string('slug')->unique();
    $t->text('deskripsi')->nullable();
    $t->string('cover_url')->nullable();
    $t->string('tema')->nullable();
    $t->boolean('is_published')->default(false);
    $t->timestamp('published_at')->nullable();
    $t->unsignedInteger('view_count')->default(0);
    $t->timestamps();
    $t->index(['is_published', 'published_at']);
});
```

---

## 4. Batch B — products

```php
Schema::create('products', function (Blueprint $t) {
    $t->id('id_product');
    $t->foreignId('id_product_category')
      ->constrained('product_categories', 'id_product_category')->cascadeOnDelete();
    $t->foreignId('id_collection')->nullable()
      ->constrained('product_collections', 'id_collection')->nullOnDelete();
    $t->string('nama');
    $t->string('slug')->unique();
    $t->text('deskripsi')->nullable();
    $t->text('key_ingredients')->nullable();
    $t->longText('full_ingredients')->nullable();   // INCI
    $t->text('cara_pemakaian')->nullable();
    $t->decimal('harga', 12, 2);                    // IDR — TANPA konversi GBP
    $t->decimal('harga_diskon', 12, 2)->nullable();
    $t->unsignedInteger('stok')->default(0);
    $t->unsignedInteger('berat_gram');              // untuk ongkir
    $t->unsignedInteger('volume_ml')->nullable();
    // ⚠️ skin_type: PRD minta MySQL SET. REKOMENDASI: pakai string comma-separated (lihat catatan
    // di bawah & doc 04 §3) agar portable ke SQLite test. Ganti baris berikut ke set(...) HANYA
    // jika test berjalan di MySQL.
    $t->string('skin_type')->nullable();            // comma-separated: "all" | "oily,dry"
    $t->string('skin_concern')->nullable();         // comma-separated
    $t->string('brand')->default('Fresh');
    $t->string('badge')->nullable();                // bestseller|new|eco|travel_size
    $t->decimal('rating', 3, 2)->default(0);
    $t->unsignedInteger('total_review')->default(0);
    $t->unsignedInteger('total_sold')->default(0);
    $t->enum('status', ['active','inactive','out_of_stock'])->default('active');
    $t->boolean('is_featured')->default(false);
    $t->string('fresh_product_id')->nullable();     // tracking sumber fresh.com (UNIQUE utk seeder idempotent)
    $t->string('fresh_url')->nullable();
    $t->timestamps();

    $t->unique('fresh_product_id');                 // updateOrCreate key (lihat doc 06 seeder)
    $t->index(['status', 'is_featured']);
    $t->index(['id_product_category', 'status']);
    $t->index(['id_collection', 'status']);
});
```
> ⚠️ `skin_type`: **dipakai `string` comma-separated** (keputusan final, lihat doc 04 §3 & §11) — bukan SET MySQL — supaya portable ke SQLite test & cast model konsisten. `fresh_product_id` dibuat unique agar `FreshProductSeeder::updateOrCreate(['fresh_product_id'=>...])` idempotent.

**Fulltext search (opsional tapi disarankan):** tambahkan di migration terpisah agar mirip pola V1 (`add_fulltext_index_to_service_nama`):
```php
DB::statement('ALTER TABLE products ADD FULLTEXT products_search_ft (nama, deskripsi, key_ingredients)');
```

---

## 5. Batch C — anak `products` / `lookbooks`

### product_images
```php
Schema::create('product_images', function (Blueprint $t) {
    $t->id('id_product_image');
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->string('image_url');
    $t->string('alt_text')->nullable();
    $t->boolean('is_primary')->default(false);
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
    $t->index(['id_product', 'is_primary']);
});
```

### wishlists
```php
Schema::create('wishlists', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->timestamp('created_at')->nullable();        // PRD: hanya created_at
    $t->unique(['id_user', 'id_product']);
});
```

### carts
```php
Schema::create('carts', function (Blueprint $t) {
    $t->id('id_cart');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->unsignedInteger('qty')->default(1);
    $t->timestamps();
    $t->unique(['id_user', 'id_product']);          // 1 baris per produk per user (qty di-update)
});
```

### lookbook_slides
```php
Schema::create('lookbook_slides', function (Blueprint $t) {
    $t->id('id_slide');
    $t->foreignId('id_lookbook')->constrained('lookbooks', 'id_lookbook')->cascadeOnDelete();
    $t->string('judul')->nullable();
    $t->text('deskripsi')->nullable();
    $t->string('image_url');
    $t->text('tips')->nullable();
    $t->unsignedInteger('sort_order')->default(0);
    $t->timestamps();
    $t->index('id_lookbook');
});
```

---

## 6. Batch D — order, payment, review

### product_orders
```php
Schema::create('product_orders', function (Blueprint $t) {
    $t->id('id_product_order');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_address')->nullable()
      ->constrained('user_addresses', 'id_address')->nullOnDelete();
    $t->foreignId('id_promo')->nullable()
      ->constrained('promo', 'id_promo')->nullOnDelete();   // reuse Promo V1
    $t->string('kode_order', 50)->unique();                 // "VYG-S-XXXXXX"
    $t->decimal('subtotal', 12, 2);
    $t->decimal('biaya_kirim', 12, 2)->default(0);
    $t->decimal('total_diskon', 12, 2)->default(0);
    $t->unsignedInteger('poin_digunakan')->default(0);
    $t->decimal('potongan_poin', 12, 2)->default(0);
    $t->decimal('grand_total', 12, 2);
    $t->string('kurir')->nullable();                        // jne|jnt|sicepat|pos
    $t->string('layanan_kirim')->nullable();                // REG|OKE|YES...
    $t->string('estimasi_tiba')->nullable();                // "2-3 hari"
    $t->string('resi')->nullable();
    $t->enum('status', ['pending','paid','processing','shipped','delivered','completed','cancelled','refunded'])
      ->default('pending');
    $t->text('catatan')->nullable();
    $t->timestamps();
    $t->index(['id_user', 'status']);
    $t->index('status');
});
```

### product_order_items
```php
Schema::create('product_order_items', function (Blueprint $t) {
    $t->id('id_item');
    $t->foreignId('id_product_order')
      ->constrained('product_orders', 'id_product_order')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
    $t->string('nama_produk');           // snapshot saat beli
    $t->unsignedInteger('qty');
    $t->decimal('harga_satuan', 12, 2);  // snapshot harga
    $t->unsignedInteger('berat_gram');   // snapshot berat
    $t->decimal('subtotal', 12, 2);
    $t->timestamps();
    $t->index('id_product_order');
});
```
> Pakai `restrictOnDelete()` ke products (jangan hilangkan histori pesanan jika produk dihapus) — atau `nullOnDelete()` jika id_product dibuat nullable. Snapshot `nama_produk`/`harga_satuan`/`berat_gram` membuat histori tetap akurat meski master produk berubah.

### product_pembayaran
```php
Schema::create('product_pembayaran', function (Blueprint $t) {
    $t->id('id_pembayaran');
    $t->foreignId('id_product_order')
      ->constrained('product_orders', 'id_product_order')->cascadeOnDelete();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->string('midtrans_order_id')->nullable();
    $t->string('midtrans_transaction_id')->nullable();
    $t->string('snap_token')->nullable();
    $t->string('metode')->nullable();
    $t->decimal('jumlah', 12, 2);
    $t->enum('status', ['pending','success','failed','expired','refund'])->default('pending');
    $t->json('raw_response')->nullable();    // tambahan: simpan payload webhook (pola V1)
    $t->timestamp('paid_at')->nullable();
    $t->timestamps();
    $t->index('id_product_order');
    $t->index('midtrans_order_id');
});
```
> ⚠️ Penamaan kolom status berbeda dari V1 (`pembayaran.status_pembayaran`). Di sini `status`. Pastikan model & service konsisten. `raw_response` ditambah (tidak di PRD) meniru `pembayaran.raw_response` V1 untuk audit webhook.

### product_reviews
```php
Schema::create('product_reviews', function (Blueprint $t) {
    $t->id('id_product_review');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->foreignId('id_product_order')
      ->constrained('product_orders', 'id_product_order')->cascadeOnDelete();
    $t->unsignedTinyInteger('rating');       // 1..5
    $t->string('judul')->nullable();
    $t->text('komentar')->nullable();
    $t->json('foto_urls')->nullable();       // array URL (max 3)
    $t->boolean('is_verified_purchase')->default(true);
    $t->boolean('is_visible')->default(true);   // tambahan: moderasi (pola review V1)
    $t->timestamps();
    $t->index(['id_product', 'is_visible']);
    $t->unique(['id_user', 'id_product', 'id_product_order']);  // cegah review ganda per item pesanan
});
```
> Tambahan `is_visible` (tidak di PRD) untuk moderasi Admin Store, meniru `review.is_visible` V1. `ProductReviewObserver` me-recalc `products.rating`/`total_review` (doc 04/05).

---

## 7. Batch E — Empty Return & poin

### empty_returns
```php
Schema::create('empty_returns', function (Blueprint $t) {
    $t->id('id_return');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_product')->nullable()
      ->constrained('products', 'id_product')->nullOnDelete();
    $t->foreignId('id_salon')->nullable()
      ->constrained('salon', 'id_salon')->nullOnDelete();   // drop-off (V1)
    $t->string('nama_produk')->nullable();                  // input manual
    $t->unsignedInteger('jumlah')->default(1);
    $t->enum('metode', ['dropoff','pickup']);
    $t->text('alamat_pickup')->nullable();
    $t->enum('status', ['pending','approved','rejected','picked_up','received'])->default('pending');
    $t->unsignedInteger('poin_earned')->default(0);
    $t->text('catatan_admin')->nullable();
    $t->foreignId('verified_by')->nullable()
      ->constrained('users', 'id_user')->nullOnDelete();
    $t->timestamp('verified_at')->nullable();
    $t->timestamps();
    $t->index(['id_user', 'status']);
    $t->index('status');
});
```

### empty_return_photos
```php
Schema::create('empty_return_photos', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_return')->constrained('empty_returns', 'id_return')->cascadeOnDelete();
    $t->string('photo_url');
    $t->timestamps();
});
```

### point_transactions
```php
Schema::create('point_transactions', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->enum('type', ['earn','spend']);
    $t->integer('amount');
    $t->string('source');                 // empty_return|purchase_discount|bonus
    $t->unsignedBigInteger('reference_id')->nullable();
    $t->string('reference_type')->nullable();   // polymorphic (mis. App\Models\EmptyReturn)
    $t->string('description')->nullable();
    $t->integer('saldo_after');
    $t->timestamps();
    $t->index(['id_user', 'type']);
    $t->index(['reference_type', 'reference_id']);
});
```

---

## 8. Batch F — Forum & gamification

### forum_threads
```php
Schema::create('forum_threads', function (Blueprint $t) {
    $t->id('id_thread');
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_forum_category')
      ->constrained('forum_categories', 'id_forum_category')->cascadeOnDelete();
    $t->string('judul');
    $t->string('slug')->unique();
    $t->longText('konten');
    $t->unsignedInteger('view_count')->default(0);
    $t->unsignedInteger('like_count')->default(0);
    $t->unsignedInteger('reply_count')->default(0);
    $t->boolean('is_pinned')->default(false);
    $t->boolean('is_locked')->default(false);
    $t->enum('status', ['published','hidden','deleted'])->default('published');
    $t->timestamps();
    $t->index(['id_forum_category', 'status']);
    $t->index(['status', 'is_pinned']);
});
```

### forum_replies
```php
Schema::create('forum_replies', function (Blueprint $t) {
    $t->id('id_reply');
    $t->foreignId('id_thread')->constrained('forum_threads', 'id_thread')->cascadeOnDelete();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('parent_id')->nullable()
      ->constrained('forum_replies', 'id_reply')->cascadeOnDelete();  // nested max 2 level
    $t->text('konten');
    $t->unsignedInteger('like_count')->default(0);
    $t->enum('status', ['published','hidden','deleted'])->default('published');
    $t->timestamps();
    $t->index(['id_thread', 'status']);
    $t->index('parent_id');
});
```

### forum_likes (polymorphic)
```php
Schema::create('forum_likes', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->string('likeable_type');          // forum_thread | forum_reply
    $t->unsignedBigInteger('likeable_id');
    $t->timestamp('created_at')->nullable();
    $t->unique(['id_user', 'likeable_type', 'likeable_id']);
    $t->index(['likeable_type', 'likeable_id']);
});
```

### forum_bookmarks
```php
Schema::create('forum_bookmarks', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->foreignId('id_thread')->constrained('forum_threads', 'id_thread')->cascadeOnDelete();
    $t->timestamp('created_at')->nullable();
    $t->unique(['id_user', 'id_thread']);
});
```

### forum_thread_tags (pivot thread ↔ product)
```php
Schema::create('forum_thread_tags', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_thread')->constrained('forum_threads', 'id_thread')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->timestamps();
    $t->unique(['id_thread', 'id_product']);
});
```

### user_badges
```php
Schema::create('user_badges', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_user')->constrained('users', 'id_user')->cascadeOnDelete();
    $t->string('badge_slug');             // skincare-guru | top-reviewer | eco-warrior | rising-star
    $t->timestamp('earned_at')->nullable();
    $t->timestamps();
    $t->unique(['id_user', 'badge_slug']);
});
```

---

## 9. Batch G — lookbook_items (pivot slide ↔ product)

```php
Schema::create('lookbook_items', function (Blueprint $t) {
    $t->id();
    $t->foreignId('id_slide')->constrained('lookbook_slides', 'id_slide')->cascadeOnDelete();
    $t->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
    $t->decimal('position_x', 5, 2)->nullable();   // % posisi tag di gambar
    $t->decimal('position_y', 5, 2)->nullable();
    $t->timestamps();
    $t->index('id_slide');
});
```

---

## 10. Ringkasan Index & Unique (untuk performa & integritas)

| Tabel | Unique | Index (selain FK auto) |
|-------|--------|------------------------|
| products | slug, fresh_product_id | (status,is_featured), (id_product_category,status), (id_collection,status), FULLTEXT(nama,deskripsi,key_ingredients) |
| product_categories/collections/forum_categories | slug | parent_id |
| product_orders | kode_order | (id_user,status), status |
| product_reviews | (id_user,id_product,id_product_order) | (id_product,is_visible) |
| carts | (id_user,id_product) | — |
| wishlists | (id_user,id_product) | — |
| user_*_profiles/points/community_points | id_user | — |
| forum_likes | (id_user,likeable_type,likeable_id) | (likeable_type,likeable_id) |
| forum_bookmarks | (id_user,id_thread) | — |
| forum_thread_tags | (id_thread,id_product) | — |
| user_badges | (id_user,badge_slug) | — |
| point_transactions | — | (id_user,type), (reference_type,reference_id) |
| empty_returns | — | (id_user,status), status |
| user_addresses | — | (id_user,is_default) |

> Pola index komposit mengikuti V1 (`add_composite_indexes_for_listing_and_orders`). Tambahkan index untuk kolom yang sering di-`where`/`orderBy` di listing (status, sort, rating).

---

## 11. Catatan tipe data

- **MySQL SET** (`products.skin_type`): multi-value (`'all'` atau `'oily,dry'`). Cast di model manual. Jika target DB bukan MySQL (mis. SQLite untuk test), `set()` jatuh ke string — pertimbangkan `string` + validasi aplikasi agar test SQLite tetap jalan. **Cek driver test** (`phpunit.xml`) sebelum pakai SET.
- **JSON** (`product_reviews.foto_urls`, `product_pembayaran.raw_response`): cast `'array'` / `'json'` di model.
- **Polymorphic** (`forum_likes`, `point_transactions.reference_*`): tidak pakai FK constraint DB (by design). Integritas dijaga di aplikasi.
- **enum** vs **Constants class**: enum DB membatasi nilai; tetap sediakan Constants class (mis. `ProductOrderStatus`) untuk dipakai di kode (doc 05).

> [!WARNING]
> Jika test pakai **SQLite in-memory**, beberapa fitur MySQL (`SET`, `FULLTEXT`, `ALTER ... MODIFY ENUM`) tidak didukung. Opsi: (a) jalankan test di MySQL, atau (b) guard fitur MySQL-only di migration dengan cek `DB::getDriverName()==='mysql'`. Putuskan di doc 08 (testing).

---

## 12. Verifikasi (acceptance Phase 1 Step 1.1–1.2)

```bash
php artisan migrate                       # semua migration jalan tanpa error
php artisan migrate:status                # 1 ALTER + 28 CREATE = 29 baris 'Ran'
# enum role
php artisan tinker --execute="dump(DB::select(\"SHOW COLUMNS FROM users LIKE 'role'\"));"
# tabel inti ada
php artisan tinker --execute="dump(Schema::hasTable('products'), Schema::hasTable('product_orders'));"
```

Acceptance: tidak ada error FK, urutan migration sesuai §2, semua unique/index terbentuk.

---

*Berikutnya: `04-eloquent-models.md` — 28 model V2 + update `User`, relasi, cast, scope, observer hooks.*
