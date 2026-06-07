<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCollection;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FreshProductSeeder extends Seeder
{
    public function run(): void
    {
        $outputDir = base_path('scripts/scraper/output');

        if (! File::isDirectory($outputDir)) {
            $this->command->warn("Output dir tidak ditemukan: {$outputDir}");
            $this->command->warn('Jalankan scraper dulu: cd scripts/scraper && go run fresh_scraper.go');

            return;
        }

        $jsonFiles = File::glob($outputDir.'/*.json');

        foreach ($jsonFiles as $file) {
            $products = json_decode(File::get($file), true);
            if (! is_array($products)) {
                continue;
            }

            foreach ($products as $data) {
                // Upsert ProductCategory (by slug)
                $category = ProductCategory::updateOrCreate(
                    ['slug' => Str::slug($data['kategori'])],
                    ['nama' => $data['kategori']]
                );

                // Upsert ProductCollection (jika ada)
                $collection = null;
                if (! empty($data['koleksi'])) {
                    $collection = ProductCollection::updateOrCreate(
                        ['slug' => Str::slug($data['koleksi'])],
                        ['nama' => $data['koleksi']]
                    );
                }

                // Upsert Product (key: fresh_product_id)
                $product = Product::updateOrCreate(
                    ['fresh_product_id' => $data['fresh_product_id']],
                    [
                        'id_product_category' => $category->id_product_category,
                        'id_collection' => $collection?->id_collection,
                        'nama' => $data['nama'],
                        'slug' => Str::slug($data['nama']),
                        'deskripsi' => $data['deskripsi'] ?? '',
                        'key_ingredients' => $data['key_ingredients'] ?? null,
                        'full_ingredients' => $data['full_ingredients'] ?? null,
                        'cara_pemakaian' => $data['cara_pemakaian'] ?? null,
                        'harga' => $data['harga_idr'],
                        'stok' => 100,
                        'berat_gram' => $data['berat_gram'] ?? 200,
                        'volume_ml' => $data['volume_ml'] ?? null,
                        'skin_type' => $data['skin_type'] ?? 'all',
                        'skin_concern' => $data['skin_concern'] ?? null,
                        'brand' => 'Fresh',
                        'badge' => $data['badge'] ?? null,
                        'fresh_url' => $data['fresh_url'] ?? null,
                        'status' => 'active',
                        'is_featured' => ($data['badge'] ?? null) === 'bestseller',
                    ]
                );

                // Upsert images (reset dulu agar idempotent)
                if (! empty($data['images'])) {
                    $product->images()->delete();
                    foreach ($data['images'] as $i => $imageUrl) {
                        ProductImage::create([
                            'id_product' => $product->id_product,
                            'image_url' => $imageUrl,
                            'is_primary' => $i === 0,
                            'sort_order' => $i,
                        ]);
                    }
                }

                // Sync kategori many-to-many (pivot category_product):
                // kategori utama + kategori tambahan dari "kategori_lain" (opsional).
                // Memungkinkan 1 produk masuk lebih dari 1 tipe.
                $categoryIds = [$category->id_product_category];
                foreach ($data['kategori_lain'] ?? [] as $namaKategori) {
                    $extra = ProductCategory::updateOrCreate(
                        ['slug' => Str::slug($namaKategori)],
                        ['nama' => $namaKategori]
                    );
                    $categoryIds[] = $extra->id_product_category;
                }
                $product->categories()->sync(array_unique($categoryIds));
            }
        }

        $this->command->info('FreshProductSeeder selesai. Total produk: '.Product::count());
    }
}
