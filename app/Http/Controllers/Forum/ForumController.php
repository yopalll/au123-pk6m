<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\CommunityPoint;
use App\Models\ForumBookmark;
use App\Models\ForumCategory;
use App\Models\ForumLike;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\ForumThreadTag;
use App\Models\Product;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::withCount(['threads' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')->get();

        $recentThreads = ForumThread::where('status', 'published')
            ->with(['user', 'category'])->latest()->limit(10)->get();

        $trendingThreads = ForumThread::where('status', 'published')
            ->orderByDesc('like_count')->orderByDesc('view_count')
            ->with(['user', 'category'])->limit(5)->get();

        $stats = [
            'member' => User::where('role', 'customer')->count(),
            'thread' => ForumThread::where('status', 'published')->count(),
            'reply' => ForumReply::where('status', 'published')->count(),
        ];

        return view('komunitas.index', compact('categories', 'recentThreads', 'trendingThreads', 'stats'));
    }

    public function kategori(ForumCategory $kategori)
    {
        $threads = ForumThread::where('id_forum_category', $kategori->id_forum_category)
            ->where('status', 'published')
            ->with('user')
            ->orderByDesc('is_pinned')->latest()
            ->paginate(20);

        $categories = ForumCategory::orderBy('sort_order')->get();

        return view('komunitas.kategori', compact('kategori', 'threads', 'categories'));
    }

    public function show(ForumThread $thread)
    {
        abort_if($thread->status !== 'published', 404);

        $thread->increment('view_count');
        $thread->load([
            'user', 'category', 'taggedProducts.primaryImage',
            'replies' => fn ($q) => $q->where('status', 'published')->whereNull('parent_id')
                ->with(['user', 'children' => fn ($c) => $c->where('status', 'published')->with('user')])
                ->latest(),
        ]);

        $isBookmarked = auth()->check() && ForumBookmark::where('id_user', auth()->id())
            ->where('id_thread', $thread->id_thread)->exists();

        $likedThread = auth()->check() && ForumLike::where('id_user', auth()->id())
            ->where('likeable_type', 'forum_thread')->where('likeable_id', $thread->id_thread)->exists();

        $likedReplies = auth()->check()
            ? ForumLike::where('id_user', auth()->id())->where('likeable_type', 'forum_reply')->pluck('likeable_id')->all()
            : [];

        return view('komunitas.thread', compact('thread', 'isBookmarked', 'likedThread', 'likedReplies'));
    }

    public function create()
    {
        $categories = ForumCategory::orderBy('sort_order')->get();
        $products = Product::where('status', 'active')->orderBy('nama')->get(['id_product', 'nama']);

        return view('komunitas.create', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_forum_category' => 'required|exists:forum_categories,id_forum_category',
            'judul' => 'required|string|min:10|max:255',
            'konten' => 'required|string|min:20',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id_product',
        ]);

        $thread = ForumThread::create([
            'id_user' => auth()->id(),
            'id_forum_category' => $request->id_forum_category,
            'judul' => $request->judul,
            'slug' => Str::slug($request->judul).'-'.Str::lower(Str::random(6)),
            'konten' => clean($request->konten),
            'status' => 'published',
        ]);

        foreach ($request->product_ids ?? [] as $pid) {
            ForumThreadTag::create(['id_thread' => $thread->id_thread, 'id_product' => $pid]);
        }

        $this->awardPoints(auth()->id(), 5); // +5 buat thread
        BadgeService::check(auth()->id());

        return redirect()->route('komunitas.thread.show', $thread->slug)
            ->with('success', 'Thread berhasil dibuat!');
    }

    public function leaderboard()
    {
        $leaders = CommunityPoint::with('user')->orderByDesc('total_points')->limit(10)->get();

        return view('komunitas.leaderboard', compact('leaders'));
    }

    public static function awardPoints(int $idUser, int $points): void
    {
        CommunityPoint::firstOrCreate(['id_user' => $idUser], ['total_points' => 0])
            ->increment('total_points', $points);
    }
}
