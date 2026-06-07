<?php

namespace Database\Seeders;

use App\Models\Lookbook;
use App\Models\LookbookItem;
use App\Models\LookbookSlide;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LookbookSeeder extends Seeder
{
    public function run(): void
    {
        $pick = fn (int $n) => Product::where('status', 'active')->inRandomOrder()->limit($n)->pluck('id_product')->all();

        $data = [
            [
                'judul' => 'Morning Glow Routine', 'tema' => 'Morning Routine',
                'cover' => 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=800',
                'deskripsi' => 'Mulai harimu dengan ritual pencerah kulit yang menyegarkan.',
                'slides' => [
                    ['judul' => 'Bersihkan & Segarkan', 'tips' => 'Gunakan air suam-suam kuku untuk membersihkan tanpa membuat kulit kering.', 'img' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=1000'],
                    ['judul' => 'Hidrasi & Lindungi', 'tips' => 'Selalu tutup dengan pelembap untuk mengunci kelembapan.', 'img' => 'https://images.unsplash.com/photo-1612817288484-6f916006741a?w=1000'],
                ],
            ],
            [
                'judul' => 'Midnight Muse', 'tema' => 'Night Care',
                'cover' => 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=800',
                'deskripsi' => 'Perawatan malam mendalam untuk kulit yang pulih saat tidur.',
                'slides' => [
                    ['judul' => 'Double Cleanse', 'tips' => 'Bersihkan ganda untuk mengangkat makeup dan kotoran sempurna.', 'img' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=1000'],
                    ['judul' => 'Treat & Repair', 'tips' => 'Aplikasikan serum dan masker malam sebagai langkah terakhir.', 'img' => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?w=1000'],
                ],
            ],
            [
                'judul' => 'Rose Hydration Ritual', 'tema' => 'Anti-Aging',
                'cover' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800',
                'deskripsi' => 'Koleksi mawar untuk hidrasi dan kekenyalan kulit maksimal.',
                'slides' => [
                    ['judul' => 'Petal Fresh', 'tips' => 'Toner mawar memberi hidrasi instan dan menyeimbangkan kulit.', 'img' => 'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?w=1000'],
                ],
            ],
        ];

        foreach ($data as $d) {
            $lb = Lookbook::updateOrCreate(
                ['slug' => Str::slug($d['judul'])],
                [
                    'judul' => $d['judul'], 'deskripsi' => $d['deskripsi'], 'cover_url' => $d['cover'],
                    'tema' => $d['tema'], 'is_published' => true, 'published_at' => now(),
                ]
            );
            $lb->slides()->delete();

            foreach ($d['slides'] as $si => $s) {
                $slide = LookbookSlide::create([
                    'id_lookbook' => $lb->id_lookbook,
                    'judul' => $s['judul'], 'deskripsi' => $d['deskripsi'],
                    'image_url' => $s['img'], 'tips' => $s['tips'], 'sort_order' => $si,
                ]);
                foreach ($pick(2) as $k => $pid) {
                    LookbookItem::create([
                        'id_slide' => $slide->id_slide, 'id_product' => $pid,
                        'position_x' => 30 + $k * 35, 'position_y' => 40 + $k * 15,
                    ]);
                }
            }
        }

        $this->command->info('LookbookSeeder selesai. Total lookbook: '.Lookbook::count());
    }
}
