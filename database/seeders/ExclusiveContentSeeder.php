<?php

namespace Database\Seeders;

use App\Models\ExclusiveContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExclusiveContentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['judul' => 'Rahasia Double Cleansing yang Benar', 'tipe' => 'article', 'min_tier' => 'bronze', 'konten' => "Double cleansing adalah kunci kulit bersih maksimal.\n\nLangkah 1: oil-based cleanser untuk melarutkan makeup & sunscreen.\nLangkah 2: water-based cleanser untuk membersihkan sisa kotoran.\n\nLakukan setiap malam untuk hasil terbaik."],
            ['judul' => '5 Tips Mengatasi Kulit Kusam', 'tipe' => 'tip', 'min_tier' => 'bronze', 'konten' => "1. Eksfoliasi 2-3x seminggu\n2. Rutin pakai vitamin C\n3. Jangan lupa sunscreen\n4. Cukup minum air\n5. Tidur berkualitas"],
            ['judul' => 'Membangun Skincare Routine Anti-Aging', 'tipe' => 'article', 'min_tier' => 'silver', 'konten' => "Anti-aging dimulai dari pencegahan.\n\nPagi: cleanser → vitamin C → moisturizer → SPF\nMalam: cleanser → retinol/peptide → moisturizer\n\nKonsisten adalah kunci."],
            ['judul' => 'Tutorial Facial Massage di Rumah', 'tipe' => 'video', 'min_tier' => 'silver', 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'konten' => 'Pijat wajah meningkatkan sirkulasi dan kekencangan kulit.'],
            ['judul' => 'Masterclass: Ingredient Layering', 'tipe' => 'article', 'min_tier' => 'gold', 'konten' => "Urutan layering bahan aktif sangat penting.\n\nAturan umum: tekstur paling cair dulu ke paling kental.\nHindari mencampur retinol + vitamin C di waktu sama.\nAHA/BHA pisahkan dari retinol."],
        ];

        foreach ($items as $i) {
            ExclusiveContent::updateOrCreate(
                ['slug' => Str::slug($i['judul'])],
                array_merge($i, ['slug' => Str::slug($i['judul']), 'is_published' => true]),
            );
        }

        $this->command->info('ExclusiveContentSeeder selesai. Total: '.ExclusiveContent::count());
    }
}
