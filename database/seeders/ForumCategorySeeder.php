<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nama' => 'Review Produk',      'slug' => 'review-produk',     'icon' => '🧴', 'deskripsi' => 'Ulasan dan diskusi produk skincare',     'sort_order' => 1],
            ['nama' => 'Tips Skincare',      'slug' => 'tips-skincare',     'icon' => '💡', 'deskripsi' => 'Tips dan rekomendasi perawatan kulit',   'sort_order' => 2],
            ['nama' => 'Routine & Lifestyle', 'slug' => 'routine-lifestyle', 'icon' => '🌿', 'deskripsi' => 'Sharing daily skincare routine',         'sort_order' => 3],
            ['nama' => 'Peduli Lingkungan',  'slug' => 'peduli-lingkungan', 'icon' => '♻️', 'deskripsi' => 'Diskusi sustainability, daur ulang',     'sort_order' => 4],
            ['nama' => 'Diskusi Umum',       'slug' => 'diskusi-umum',      'icon' => '💬', 'deskripsi' => 'Topik bebas terkait beauty & wellness', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            ForumCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $this->command->info('5 forum kategori berhasil di-seed.');
    }
}
