<?php

namespace App\Http\Controllers;

use App\Models\ExclusiveContent;
use App\Services\PointService;

class ExclusiveContentController extends Controller
{
    private const ORDER = ['starter' => 0, 'bronze' => 1, 'silver' => 2, 'gold' => 3];

    public function index()
    {
        $userTier = PointService::getOrCreate(auth()->id())->tier;
        $userLevel = self::ORDER[$userTier] ?? 0;

        $contents = ExclusiveContent::where('is_published', true)
            ->latest()->get()
            ->map(function ($c) use ($userLevel) {
                $c->is_accessible = $userLevel >= (self::ORDER[$c->min_tier] ?? 99);

                return $c;
            });

        return view('exclusive.index', compact('contents', 'userTier'));
    }

    public function show(string $slug)
    {
        $content = ExclusiveContent::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $userTier = PointService::getOrCreate(auth()->id())->tier;
        $userLevel = self::ORDER[$userTier] ?? 0;

        if ($userLevel < (self::ORDER[$content->min_tier] ?? 99)) {
            return redirect()->route('exclusive.index')
                ->with('error', "Konten ini butuh tier {$content->min_tier} atau lebih tinggi.");
        }

        return view('exclusive.show', compact('content'));
    }
}
