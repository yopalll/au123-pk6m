<?php

namespace Database\Seeders;

use App\Models\CommunityPoint;
use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ForumThreadSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'customer')->limit(5)->get();
        if ($users->isEmpty()) {
            $users = User::limit(5)->get();
        }
        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user untuk seed forum.');

            return;
        }

        $cats = ForumCategory::pluck('id_forum_category', 'slug');

        $threads = [
            ['review-produk', 'Review jujur: Black Tea Kombucha Essence worth it?', 'Sudah pakai 2 minggu dan kulit terasa lebih lembap. Ada yang punya pengalaman serupa? Share dong!'],
            ['tips-skincare', 'Tips layering serum yang benar untuk pemula', 'Banyak yang masih bingung urutan serum. Aturan dasarnya: dari tekstur paling cair ke kental. Yuk diskusi!'],
            ['routine-lifestyle', 'Morning routine simpel 5 langkah', 'Cleanser, toner, serum, moisturizer, sunscreen. Cukup itu aja sebenarnya. Kalian gimana?'],
            ['peduli-lingkungan', 'Pengalaman ikut Empty Return VIYGO', 'Baru pertama kali kembaliin botol kosong, dapat poin lumayan. Recommended banget buat yang peduli lingkungan!'],
            ['diskusi-umum', 'Skincare favorit yang repurchase terus', 'Kalau aku Rose Toner, udah botol ke-3. Kalian apa nih yang selalu repurchase?'],
        ];

        foreach ($threads as $i => [$catSlug, $judul, $konten]) {
            $author = $users[$i % $users->count()];
            $thread = ForumThread::updateOrCreate(
                ['slug' => Str::slug($judul)],
                [
                    'id_user' => $author->id_user,
                    'id_forum_category' => $cats[$catSlug] ?? $cats->first(),
                    'judul' => $judul,
                    'konten' => '<p>'.$konten.'</p>',
                    'view_count' => rand(20, 200),
                    'like_count' => rand(2, 25),
                    'reply_count' => 0,
                    'status' => 'published',
                ]
            );

            // 2 balasan per thread
            foreach (range(1, 2) as $r) {
                $replier = $users[($i + $r) % $users->count()];
                ForumReply::create([
                    'id_thread' => $thread->id_thread,
                    'id_user' => $replier->id_user,
                    'konten' => '<p>Setuju banget! Makasih sharingnya 🙌</p>',
                    'like_count' => rand(0, 8),
                    'status' => 'published',
                ]);
            }
            $thread->update(['reply_count' => 2]);

            CommunityPoint::firstOrCreate(['id_user' => $author->id_user], ['total_points' => 0])
                ->increment('total_points', 5 + $thread->like_count * 2 + 2);
        }

        $this->command->info('ForumThreadSeeder selesai. Threads: '.ForumThread::count());
    }
}
