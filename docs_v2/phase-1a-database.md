# Phase 1A — Database Foundation
## Step 1.1: ALTER users.role + Step 1.2: 28 Migration Tabel Baru

> **Prerequisite:** Tidak ada (step pertama)  
> **Verifikasi akhir:** `php artisan migrate:status` → 29 migration berstatus `Ran`

---

## KONTEKS PENTING

Proyek ini adalah VIYGO V2 (Laravel 12, Livewire Flux, TailwindCSS v4). V1 sudah ada dengan tabel:
`users` (PK: `id_user`), `salon` (PK: `id_salon`), `order`, `pembayaran`, `review`, `kategori`, `kota`, `promo` (PK: `id_promo`).

**Konvensi yang WAJIB diikuti:**
- PK custom: `id_product`, `id_cart`, dll. (BUKAN `id` default Laravel) — kecuali tabel pivot kecil
- FK ke tabel V1 WAJIB specify nama kolom: `->constrained('users', 'id_user')`
- Jangan ubah tabel V1 kecuali ALTER `users.role` di Step 1.1

---

## STEP 1.1 — ALTER Table `users` (Tambah Role `admin_store`)

Buat migration file:

**File:** `database/migrations/2026_06_01_000001_add_admin_store_role_to_users.php`

```php
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
    DB::statement(
        "ALTER TABLE `users` MODIFY `role` "
        . "ENUM('customer','salon_owner','admin') "
        . "NOT NULL DEFAULT 'customer'"
    );
}
```

Jalankan: `php artisan migrate`  
Verifikasi: `SHOW COLUMNS FROM users LIKE 'role'` → 4 value enum.

---

## STEP 1.2 — 28 Migration Tabel Baru

Buat semua migration berikut **sesuai urutan** (dependency tabel induk harus ada duluan).

---

### BATCH A — Tabel induk tanpa FK ke tabel V2 lain

#### 01. `product_categories`
```php
Schema::create('product_categories', function (Blueprint $table) {
    $table->id('id_product_category');
    $table->string('nama');
    $table->string('slug')->unique();
    $table->text('deskripsi')->nullable();
    $table->string('icon_url')->nullable();
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->foreign('parent_id')->references('id_product_category')->on('product_categories')->nullOnDelete();
});
```

#### 02. `product_collections`
```php
Schema::create('product_collections', function (Blueprint $table) {
    $table->id('id_collection');
    $table->string('nama'); // "Black Tea", "Rose", "Soy"
    $table->string('slug')->unique();
    $table->text('deskripsi')->nullable();
    $table->string('banner_url')->nullable();
    $table->string('tagline')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

#### 03. `user_addresses`
```php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->id('id_address');
    $table->unsignedBigInteger('id_user');
    $table->string('label'); // "Rumah", "Kantor"
    $table->string('nama_penerima');
    $table->string('phone');
    $table->text('alamat_lengkap');
    $table->string('kota'); // nama kota dari api.co.id
    $table->string('kota_id'); // ID kota dari api.co.id
    $table->string('provinsi');
    $table->string('provinsi_id');
    $table->string('kode_pos');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
    // CATATAN: TIDAK FK ke tabel kota V1, domain data berbeda (api.co.id vs Treatwell)
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

#### 04. `user_skincare_profiles`
```php
Schema::create('user_skincare_profiles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user')->unique();
    $table->enum('skin_type', ['oily','dry','combination','sensitive','normal']);
    $table->string('skin_concerns'); // comma-separated
    $table->timestamp('updated_at')->nullable();
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

#### 05. `user_points`
```php
Schema::create('user_points', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user')->unique();
    $table->integer('saldo')->default(0);
    $table->integer('total_earned')->default(0);
    $table->integer('total_spent')->default(0);
    $table->enum('tier', ['starter','bronze','silver','gold'])->default('starter');
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

#### 06. `community_points`
```php
Schema::create('community_points', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user')->unique();
    $table->integer('total_points')->default(0);
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

#### 07. `forum_categories`
```php
Schema::create('forum_categories', function (Blueprint $table) {
    $table->id('id_forum_category');
    $table->string('nama');
    $table->string('slug')->unique();
    $table->text('deskripsi')->nullable();
    $table->string('icon')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

---

### BATCH B — Tabel yang butuh Batch A

#### 08. `products`
```php
Schema::create('products', function (Blueprint $table) {
    $table->id('id_product');
    $table->unsignedBigInteger('id_product_category');
    $table->unsignedBigInteger('id_collection')->nullable();
    $table->string('nama');
    $table->string('slug')->unique();
    $table->text('deskripsi');
    $table->text('key_ingredients')->nullable();
    $table->longText('full_ingredients')->nullable();
    $table->text('cara_pemakaian')->nullable();
    $table->decimal('harga', 10, 2);
    $table->decimal('harga_diskon', 10, 2)->nullable();
    $table->integer('stok')->default(0);
    $table->integer('berat_gram'); // untuk kalkulasi ongkir
    $table->integer('volume_ml')->nullable();
    $table->string('skin_type')->default('all'); // 'all','oily','dry','combination','sensitive','normal'
    $table->string('skin_concern')->nullable(); // comma-separated: 'dehydration,dullness,acne'
    $table->string('brand')->default('Fresh');
    $table->string('badge')->nullable(); // 'bestseller','new','eco','travel_size'
    $table->decimal('rating', 3, 2)->default(0);
    $table->integer('total_review')->default(0);
    $table->integer('total_sold')->default(0);
    $table->enum('status', ['active','inactive','out_of_stock'])->default('active');
    $table->boolean('is_featured')->default(false);
    $table->string('fresh_product_id')->nullable(); // ID dari fresh.com
    $table->string('fresh_url')->nullable(); // URL sumber di fresh.com
    $table->timestamps();
    $table->foreign('id_product_category')->references('id_product_category')->on('product_categories');
    $table->foreign('id_collection')->references('id_collection')->on('product_collections')->nullOnDelete();
});
```

#### 09. `lookbooks`
```php
Schema::create('lookbooks', function (Blueprint $table) {
    $table->id('id_lookbook');
    $table->string('judul');
    $table->string('slug')->unique();
    $table->text('deskripsi');
    $table->string('cover_url');
    $table->string('tema');
    $table->boolean('is_published')->default(false);
    $table->dateTime('published_at')->nullable();
    $table->integer('view_count')->default(0);
    $table->timestamps();
});
```

#### 10. `exclusive_contents`
```php
Schema::create('exclusive_contents', function (Blueprint $table) {
    $table->id('id_content');
    $table->string('judul');
    $table->string('slug')->unique();
    $table->enum('tipe', ['article','video','tip']);
    $table->longText('konten')->nullable();
    $table->string('video_url')->nullable();
    $table->string('thumbnail_url')->nullable();
    $table->enum('min_tier', ['bronze','silver','gold']);
    $table->boolean('is_published')->default(false);
    $table->timestamps();
});
```

---

### BATCH C — Tabel yang butuh `products`

#### 11. `product_images`
```php
Schema::create('product_images', function (Blueprint $table) {
    $table->id('id_product_image');
    $table->unsignedBigInteger('id_product');
    $table->string('image_url');
    $table->string('alt_text')->nullable();
    $table->boolean('is_primary')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
});
```

#### 12. `wishlists`
```php
Schema::create('wishlists', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_product');
    $table->timestamp('created_at')->nullable();
    $table->unique(['id_user', 'id_product']);
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
    $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
});
```

#### 13. `carts`
```php
Schema::create('carts', function (Blueprint $table) {
    $table->id('id_cart');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_product');
    $table->integer('qty');
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
    $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
});
```

#### 14. `lookbook_slides`
```php
Schema::create('lookbook_slides', function (Blueprint $table) {
    $table->id('id_slide');
    $table->unsignedBigInteger('id_lookbook');
    $table->string('judul')->nullable();
    $table->text('deskripsi')->nullable();
    $table->string('image_url');
    $table->text('tips')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->foreign('id_lookbook')->references('id_lookbook')->on('lookbooks')->cascadeOnDelete();
});
```

---

### BATCH D — Tabel order & payment

#### 15. `product_orders`
```php
Schema::create('product_orders', function (Blueprint $table) {
    $table->id('id_product_order');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_address');
    $table->unsignedBigInteger('id_promo')->nullable();
    // Format: "VYG-S-XXXXXX" — berbeda dari booking V1 "VYG-XXXXXX"
    $table->string('kode_order')->unique();
    $table->decimal('subtotal', 10, 2);
    $table->decimal('biaya_kirim', 10, 2);
    $table->decimal('total_diskon', 10, 2)->default(0);
    $table->integer('poin_digunakan')->default(0);
    $table->decimal('potongan_poin', 10, 2)->default(0);
    $table->decimal('grand_total', 10, 2);
    $table->string('kurir'); // 'jne','jnt','sicepat','pos'
    $table->string('layanan_kirim'); // 'REG','OKE','YES','EZ', dll.
    $table->string('estimasi_tiba')->nullable(); // '2-3 hari'
    $table->string('resi')->nullable();
    $table->enum('status', ['pending','paid','processing','shipped','delivered','completed','cancelled','refunded'])->default('pending');
    $table->text('catatan')->nullable();
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users');
    $table->foreign('id_address')->references('id_address')->on('user_addresses');
    $table->foreign('id_promo')->references('id_promo')->on('promo')->nullOnDelete();
});
```

#### 16. `product_order_items`
```php
Schema::create('product_order_items', function (Blueprint $table) {
    $table->id('id_item');
    $table->unsignedBigInteger('id_product_order');
    $table->unsignedBigInteger('id_product');
    $table->string('nama_produk'); // snapshot saat beli
    $table->integer('qty');
    $table->decimal('harga_satuan', 10, 2);
    $table->integer('berat_gram'); // snapshot berat saat beli
    $table->decimal('subtotal', 10, 2);
    $table->timestamps();
    $table->foreign('id_product_order')->references('id_product_order')->on('product_orders')->cascadeOnDelete();
    $table->foreign('id_product')->references('id_product')->on('products');
});
```

#### 17. `product_pembayaran`
```php
Schema::create('product_pembayaran', function (Blueprint $table) {
    $table->id('id_pembayaran');
    $table->unsignedBigInteger('id_product_order');
    $table->unsignedBigInteger('id_user');
    $table->string('midtrans_order_id')->nullable();
    $table->string('midtrans_transaction_id')->nullable();
    $table->string('snap_token')->nullable();
    $table->string('metode')->nullable();
    $table->decimal('jumlah', 10, 2);
    $table->enum('status', ['pending','success','failed','expired','refund'])->default('pending');
    $table->dateTime('paid_at')->nullable();
    $table->timestamps();
    $table->foreign('id_product_order')->references('id_product_order')->on('product_orders')->cascadeOnDelete();
    $table->foreign('id_user')->references('id_user')->on('users');
});
```

#### 18. `product_reviews`
```php
Schema::create('product_reviews', function (Blueprint $table) {
    $table->id('id_product_review');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_product');
    $table->unsignedBigInteger('id_product_order');
    $table->tinyInteger('rating'); // 1-5
    $table->string('judul')->nullable();
    $table->text('komentar')->nullable();
    $table->json('foto_urls')->nullable(); // array of photo URLs
    $table->boolean('is_verified_purchase')->default(true);
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users');
    $table->foreign('id_product')->references('id_product')->on('products');
    $table->foreign('id_product_order')->references('id_product_order')->on('product_orders');
});
```

---

### BATCH E — Empty Return

#### 19. `empty_returns`
```php
Schema::create('empty_returns', function (Blueprint $table) {
    $table->id('id_return');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_product')->nullable();
    $table->unsignedBigInteger('id_salon')->nullable();
    $table->string('nama_produk'); // untuk input manual
    $table->integer('jumlah');
    $table->enum('metode', ['dropoff','pickup']);
    $table->text('alamat_pickup')->nullable();
    $table->enum('status', ['pending','approved','rejected','picked_up','received'])->default('pending');
    $table->integer('poin_earned')->default(0);
    $table->text('catatan_admin')->nullable();
    $table->unsignedBigInteger('verified_by')->nullable();
    $table->dateTime('verified_at')->nullable();
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users');
    $table->foreign('id_product')->references('id_product')->on('products')->nullOnDelete();
    $table->foreign('id_salon')->references('id_salon')->on('salon')->nullOnDelete();
    $table->foreign('verified_by')->references('id_user')->on('users')->nullOnDelete();
});
```

#### 20. `empty_return_photos`
```php
Schema::create('empty_return_photos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_return');
    $table->string('photo_url');
    $table->timestamp('created_at')->nullable();
    $table->foreign('id_return')->references('id_return')->on('empty_returns')->cascadeOnDelete();
});
```

#### 21. `point_transactions`
```php
Schema::create('point_transactions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user');
    $table->enum('type', ['earn','spend']);
    $table->integer('amount');
    $table->string('source'); // 'empty_return','purchase_discount','bonus'
    $table->integer('reference_id')->nullable();
    $table->string('reference_type')->nullable(); // polymorphic
    $table->string('description');
    $table->integer('saldo_after');
    $table->timestamp('created_at')->nullable();
    $table->foreign('id_user')->references('id_user')->on('users');
});
```

---

### BATCH F — Forum

#### 22. `forum_threads`
```php
Schema::create('forum_threads', function (Blueprint $table) {
    $table->id('id_thread');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_forum_category');
    $table->string('judul');
    $table->string('slug')->unique();
    $table->longText('konten');
    $table->integer('view_count')->default(0);
    $table->integer('like_count')->default(0);
    $table->integer('reply_count')->default(0);
    $table->boolean('is_pinned')->default(false);
    $table->boolean('is_locked')->default(false);
    $table->enum('status', ['published','hidden','deleted'])->default('published');
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users');
    $table->foreign('id_forum_category')->references('id_forum_category')->on('forum_categories');
});
```

#### 23. `forum_replies`
```php
Schema::create('forum_replies', function (Blueprint $table) {
    $table->id('id_reply');
    $table->unsignedBigInteger('id_thread');
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('parent_id')->nullable(); // nested reply
    $table->text('konten');
    $table->integer('like_count')->default(0);
    $table->enum('status', ['published','hidden','deleted'])->default('published');
    $table->timestamps();
    $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
    $table->foreign('id_user')->references('id_user')->on('users');
    $table->foreign('parent_id')->references('id_reply')->on('forum_replies')->nullOnDelete();
});
```

#### 24. `forum_likes` (polymorphic)
```php
Schema::create('forum_likes', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user');
    $table->string('likeable_type'); // 'forum_thread' atau 'forum_reply'
    $table->unsignedBigInteger('likeable_id');
    $table->timestamp('created_at')->nullable();
    $table->unique(['id_user', 'likeable_type', 'likeable_id']);
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

#### 25. `forum_bookmarks`
```php
Schema::create('forum_bookmarks', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user');
    $table->unsignedBigInteger('id_thread');
    $table->timestamp('created_at')->nullable();
    $table->unique(['id_user', 'id_thread']);
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
    $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
});
```

#### 26. `forum_thread_tags` (pivot thread ↔ products)
```php
Schema::create('forum_thread_tags', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_thread');
    $table->unsignedBigInteger('id_product');
    $table->timestamps();
    $table->foreign('id_thread')->references('id_thread')->on('forum_threads')->cascadeOnDelete();
    $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
});
```

#### 27. `user_badges`
```php
Schema::create('user_badges', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_user');
    $table->string('badge_slug'); // 'skincare_guru','top_reviewer','eco_warrior','rising_star'
    $table->dateTime('earned_at');
    $table->timestamps();
    $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
});
```

---

### BATCH G — Lookbook pivot

#### 28. `lookbook_items` (pivot slide ↔ products)
```php
Schema::create('lookbook_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_slide');
    $table->unsignedBigInteger('id_product');
    $table->decimal('position_x', 5, 2)->default(0); // posisi tag di gambar (%)
    $table->decimal('position_y', 5, 2)->default(0);
    $table->timestamps();
    $table->foreign('id_slide')->references('id_slide')->on('lookbook_slides')->cascadeOnDelete();
    $table->foreign('id_product')->references('id_product')->on('products')->cascadeOnDelete();
});
```

---

## VERIFIKASI AKHIR

```bash
php artisan migrate
php artisan migrate:status
# Harus menampilkan 29 migration berstatus "Ran":
# - 1 ALTER (add_admin_store_role_to_users)
# - 28 CREATE (semua tabel baru)
```

Setelah berhasil, lanjutkan ke **[phase-1b-models.md](phase-1b-models.md)**.
