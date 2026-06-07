<?php

namespace App\Services;

use App\Models\CommunityPoint;
use App\Models\EmptyReturn;
use App\Models\ForumThread;
use App\Models\ProductReview;
use App\Models\UserBadge;

class BadgeService
{
    /** Cek & assign badge yang layak untuk user (idempotent). */
    public static function check(int $idUser): void
    {
        $owned = UserBadge::where('id_user', $idUser)->pluck('badge_slug')->all();

        $ecoCount = EmptyReturn::where('id_user', $idUser)->where('status', 'approved')->count();
        $reviewCount = ProductReview::where('id_user', $idUser)->count();
        $tipsCount = ForumThread::where('id_user', $idUser)
            ->whereHas('category', fn ($q) => $q->where('slug', 'tips-skincare'))->count();
        $communityPoints = (int) (CommunityPoint::where('id_user', $idUser)->value('total_points') ?? 0);

        $candidates = [
            'eco_warrior' => $ecoCount >= 5,
            'top_reviewer' => $reviewCount >= 10,
            'skincare_guru' => $tipsCount >= 20,
            'rising_star' => $communityPoints >= 50,
        ];

        foreach ($candidates as $slug => $earned) {
            if ($earned && ! in_array($slug, $owned, true)) {
                UserBadge::create(['id_user' => $idUser, 'badge_slug' => $slug, 'earned_at' => now()]);
            }
        }
    }

    public static function label(string $slug): string
    {
        return [
            'eco_warrior' => '♻️ Eco Warrior',
            'top_reviewer' => '⭐ Top Reviewer',
            'skincare_guru' => '🧴 Skincare Guru',
            'rising_star' => '🔥 Rising Star',
        ][$slug] ?? $slug;
    }
}
