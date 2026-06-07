<?php

namespace App\Http\Controllers;

use App\Models\ExclusiveContent;
use App\Models\PointTransaction;
use App\Models\UserBadge;
use App\Services\PointService;

class PointController extends Controller
{
    public function index()
    {
        $userPoint = PointService::getOrCreate(auth()->id());

        $tiers = array_keys(PointService::TIERS);
        $currentIdx = array_search($userPoint->tier, $tiers, true);
        $nextTier = $tiers[$currentIdx + 1] ?? null;
        $nextThreshold = $nextTier ? PointService::TIERS[$nextTier] : null;
        $progress = $nextThreshold
            ? min(100, round($userPoint->total_earned / $nextThreshold * 100))
            : 100;

        $accessibleTiers = array_slice($tiers, 0, $currentIdx + 1);
        $exclusiveContents = ExclusiveContent::where('is_published', true)
            ->whereIn('min_tier', $accessibleTiers)
            ->latest()->limit(6)->get();

        $recentTransactions = PointTransaction::where('id_user', auth()->id())
            ->latest()->limit(8)->get();

        $badges = UserBadge::where('id_user', auth()->id())->get();

        return view('akun.poin', compact(
            'userPoint', 'nextTier', 'nextThreshold', 'progress',
            'exclusiveContents', 'recentTransactions', 'badges'
        ));
    }

    public function history()
    {
        $transactions = PointTransaction::where('id_user', auth()->id())
            ->latest()->paginate(20);

        return view('akun.poin-history', compact('transactions'));
    }
}
