# Phase 3C — Modul 4: Digital Library Community (Forum)
## Step 3.3

> **Prerequisite:** Phase 1B (Models), Phase 1C (ForumCategorySeeder — 5 kategori sudah ada)  
> **Bisa dikerjakan paralel dengan Phase 3A dan 3B**  
> **Design Reference:** Tidak ada desain khusus forum di `docs_v2/design/` — gunakan design system Serene Floral Noir dari `docs_v2/design/DESIGN-SYSTEM.md`  
> **Verifikasi:** Buat thread → reply → like → bookmark → poin komunitas bertambah → badge ter-assign

---

## SUB-STEP 3.3.1 — Routes

```php
// Forum Komunitas
Route::prefix('komunitas')->name('komunitas.')->group(function () {
    Route::get('/',                              [ForumController::class, 'index'])->name('index');
    Route::get('/leaderboard',                   [ForumController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/{kategori:slug}',               [ForumController::class, 'kategori'])->name('kategori');
    Route::get('/thread/{thread:slug}',          [ForumController::class, 'show'])->name('thread.show');

    Route::middleware('auth')->group(function () {
        Route::get('/thread/buat',               [ForumController::class, 'create'])->name('thread.create');
        Route::post('/thread',                   [ForumController::class, 'store'])->name('thread.store');
        Route::post('/thread/{thread:slug}/reply',    [ForumReplyController::class, 'store'])->name('reply.store');
        Route::post('/thread/{thread:slug}/like',     [ForumInteractionController::class, 'likeThread'])->name('thread.like');
        Route::post('/reply/{reply}/like',            [ForumInteractionController::class, 'likeReply'])->name('reply.like');
        Route::post('/thread/{thread:slug}/bookmark', [ForumInteractionController::class, 'bookmark'])->name('thread.bookmark');
    });
});

Route::get('/akun/bookmarks', [ForumInteractionController::class, 'bookmarks'])->name('akun.bookmarks')->middleware('auth');
```

---

## SUB-STEP 3.3.2 — ForumController

**File:** `app/Http/Controllers/Forum/ForumController.php`

```php
<?php
namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\{ForumCategory, ForumThread, ForumBookmark, CommunityPoint};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    public function index()
    {
        $categories     = ForumCategory::withCount('threads')->orderBy('sort_order')->get();
        $recentThreads  = ForumThread::where('status', 'published')
                                     ->with(['user', 'category'])
                                     ->latest()->limit(10)->get();
        $trendingThreads= ForumThread::where('status', 'published')
                                     ->orderByDesc('like_count')
                                     ->with(['user', 'category'])
                                     ->limit(5)->get();

        $stats = [
            'total_member' => \App\Models\User::where('role', 'customer')->count(),
            'total_thread' => ForumThread::where('status', 'published')->count(),
            'total_reply'  => \App\Models\ForumReply::where('status', 'published')->count(),
        ];

        return view('komunitas.index', compact('categories', 'recentThreads', 'trendingThreads', 'stats'));
    }

    public function kategori(ForumCategory $kategori)
    {
        $threads = ForumThread::where('id_forum_category', $kategori->id_forum_category)
                              ->where('status', 'published')
                              ->with(['user'])
                              ->orderByDesc('is_pinned')
                              ->latest()
                              ->paginate(20);

        $categories = ForumCategory::orderBy('sort_order')->get();

        return view('komunitas.kategori', compact('kategori', 'threads', 'categories'));
    }

    public function show(ForumThread $thread)
    {
        abort_if($thread->status !== 'published', 404);

        $thread->increment('view_count');
        $thread->load(['user', 'category', 'taggedProducts.primaryImage',
                       'replies' => fn($q) => $q->where('status', 'published')
                                                 ->whereNull('parent_id')
                                                 ->with(['user', 'children.user'])
                                                 ->latest()]);

        $isBookmarked = auth()->check()
            ? \App\Models\ForumBookmark::where('id_user', auth()->user()->id_user)
                                       ->where('id_thread', $thread->id_thread)->exists()
            : false;

        $likedReplies = auth()->check()
            ? \App\Models\ForumLike::where('id_user', auth()->user()->id_user)
                                   ->where('likeable_type', 'forum_reply')
                                   ->pluck('likeable_id')->toArray()
            : [];

        return view('komunitas.thread', compact('thread', 'isBookmarked', 'likedReplies'));
    }

    public function create()
    {
        $categories = ForumCategory::orderBy('sort_order')->get();
        $products   = \App\Models\Product::where('status', 'active')->get(['id_product', 'nama']);
        return view('komunitas.create', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_forum_category' => 'required|exists:forum_categories,id_forum_category',
            'judul'             => 'required|string|min:10|max:255',
            'konten'            => 'required|string|min:20',
            'product_ids'       => 'nullable|array',
            'product_ids.*'     => 'exists:products,id_product',
        ]);

        $user = auth()->user();

        $thread = ForumThread::create([
            'id_user'           => $user->id_user,
            'id_forum_category' => $request->id_forum_category,
            'judul'             => $request->judul,
            'slug'              => Str::slug($request->judul) . '-' . Str::random(6),
            'konten'            => clean($request->konten), // HTMLPurifier
            'status'            => 'published',
        ]);

        // Tag produk
        if ($request->product_ids) {
            foreach ($request->product_ids as $productId) {
                \App\Models\ForumThreadTag::create(['id_thread' => $thread->id_thread, 'id_product' => $productId]);
            }
        }

        // Award poin komunitas (+5 untuk buat thread)
        $this->awardCommunityPoints($user->id_user, 5);

        // Check badge
        $this->checkBadges($user->id_user);

        return redirect()->route('komunitas.thread.show', $thread->slug)
                         ->with('success', 'Thread berhasil dibuat!');
    }

    public function leaderboard()
    {
        $leaders = CommunityPoint::with('user')
                                 ->orderByDesc('total_points')
                                 ->limit(10)->get();

        return view('komunitas.leaderboard', compact('leaders'));
    }

    private function awardCommunityPoints(int $userId, int $points): void
    {
        $cp = CommunityPoint::firstOrCreate(['id_user' => $userId], ['total_points' => 0]);
        $cp->increment('total_points', $points);
    }

    private function checkBadges(int $userId): void
    {
        $user      = \App\Models\User::find($userId);
        $cp        = CommunityPoint::where('id_user', $userId)->first();
        $badges    = \App\Models\UserBadge::where('id_user', $userId)->pluck('badge_slug');

        // Rising Star: 50+ poin komunitas
        if ($cp && $cp->total_points >= 50 && !$badges->contains('rising_star')) {
            \App\Models\UserBadge::create(['id_user' => $userId, 'badge_slug' => 'rising_star', 'earned_at' => now()]);
        }

        // Skincare Guru: 20+ tips threads
        $tipsCount = ForumThread::where('id_user', $userId)
            ->whereHas('category', fn($q) => $q->where('slug', 'tips-skincare'))
            ->count();
        if ($tipsCount >= 20 && !$badges->contains('skincare_guru')) {
            \App\Models\UserBadge::create(['id_user' => $userId, 'badge_slug' => 'skincare_guru', 'earned_at' => now()]);
        }

        // Eco Warrior: 5+ empty returns
        $ecoCount = \App\Models\EmptyReturn::where('id_user', $userId)->where('status', 'approved')->count();
        if ($ecoCount >= 5 && !$badges->contains('eco_warrior')) {
            \App\Models\UserBadge::create(['id_user' => $userId, 'badge_slug' => 'eco_warrior', 'earned_at' => now()]);
        }

        // Top Reviewer: 10+ product reviews
        $reviewCount = \App\Models\ProductReview::where('id_user', $userId)->count();
        if ($reviewCount >= 10 && !$badges->contains('top_reviewer')) {
            \App\Models\UserBadge::create(['id_user' => $userId, 'badge_slug' => 'top_reviewer', 'earned_at' => now()]);
        }
    }
}
```

---

## SUB-STEP 3.3.3 — ForumReplyController

**File:** `app/Http/Controllers/Forum/ForumReplyController.php`

```php
public function store(Request $request, ForumThread $thread)
{
    abort_if($thread->status !== 'published' || $thread->is_locked, 403);

    $request->validate([
        'konten'    => 'required|string|min:5|max:5000',
        'parent_id' => 'nullable|exists:forum_replies,id_reply',
    ]);

    // Batas nesting: max 2 level
    if ($request->parent_id) {
        $parent = \App\Models\ForumReply::find($request->parent_id);
        abort_if($parent && $parent->parent_id !== null, 422, 'Nesting terlalu dalam (max 2 level).');
    }

    $reply = \App\Models\ForumReply::create([
        'id_thread' => $thread->id_thread,
        'id_user'   => auth()->user()->id_user,
        'parent_id' => $request->parent_id,
        'konten'    => clean($request->konten), // HTMLPurifier
        'status'    => 'published',
    ]);

    // Update reply count di thread
    $thread->increment('reply_count');

    // Award poin komunitas ke penulis thread (+1 dapat reply)
    $this->awardCommunityPoints($thread->id_user, 1);
    // Award penulis reply juga (buat reply = kontribusi)
    // (tidak di-spec PRD, opsional)

    return back()->with('success', 'Balasan berhasil dikirim!');
}
```

---

## SUB-STEP 3.3.4 — ForumInteractionController

**File:** `app/Http/Controllers/Forum/ForumInteractionController.php`

```php
<?php
namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\{ForumThread, ForumReply, ForumLike, ForumBookmark, CommunityPoint};

class ForumInteractionController extends Controller
{
    public function likeThread(ForumThread $thread)
    {
        $userId   = auth()->user()->id_user;
        $existing = ForumLike::where('id_user', $userId)
                             ->where('likeable_type', 'forum_thread')
                             ->where('likeable_id', $thread->id_thread)->first();
        if ($existing) {
            $existing->delete();
            $thread->decrement('like_count');
            $liked = false;
        } else {
            ForumLike::create(['id_user' => $userId, 'likeable_type' => 'forum_thread', 'likeable_id' => $thread->id_thread]);
            $thread->increment('like_count');
            $liked = true;

            // Award poin ke penulis thread (+2 dapat like)
            $cp = CommunityPoint::firstOrCreate(['id_user' => $thread->id_user], ['total_points' => 0]);
            $cp->increment('total_points', 2);
        }

        return response()->json(['liked' => $liked, 'like_count' => $thread->fresh()->like_count]);
    }

    public function likeReply(ForumReply $reply)
    {
        $userId   = auth()->user()->id_user;
        $existing = ForumLike::where('id_user', $userId)
                             ->where('likeable_type', 'forum_reply')
                             ->where('likeable_id', $reply->id_reply)->first();
        if ($existing) {
            $existing->delete();
            $reply->decrement('like_count');
            $liked = false;
        } else {
            ForumLike::create(['id_user' => $userId, 'likeable_type' => 'forum_reply', 'likeable_id' => $reply->id_reply]);
            $reply->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $reply->fresh()->like_count]);
    }

    public function bookmark(ForumThread $thread)
    {
        $userId   = auth()->user()->id_user;
        $existing = ForumBookmark::where('id_user', $userId)->where('id_thread', $thread->id_thread)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            ForumBookmark::create(['id_user' => $userId, 'id_thread' => $thread->id_thread]);
            $bookmarked = true;
        }

        return response()->json(['bookmarked' => $bookmarked]);
    }

    public function bookmarks()
    {
        $bookmarks = ForumBookmark::where('id_user', auth()->user()->id_user)
                                  ->with(['thread.user', 'thread.category'])
                                  ->latest()->paginate(15);

        return view('akun.bookmarks', compact('bookmarks'));
    }
}
```

---

## SUB-STEP 3.3.5 — HTMLPurifier Setup

Forum menggunakan rich text input (bisa `<b>`, `<i>`, `<ul>`, `<a>` dll.) — XSS protection wajib.

Install HTMLPurifier:
```bash
composer require ezyang/htmlpurifier
```

Buat helper function di `app/helpers.php` (atau `AppServiceProvider`):

```php
if (!function_exists('clean')) {
    function clean(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,strong,i,em,ul,ol,li,a[href],br,blockquote,h3,h4');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
```

Daftarkan di `composer.json`:
```json
"autoload": {
    "files": ["app/helpers.php"]
}
```
Lalu `composer dump-autoload`.

---

## SUB-STEP 3.3.6 — Views

| View | Deskripsi |
|------|-----------|
| `resources/views/komunitas/index.blade.php` | Home forum: kategori, trending, recent threads, stats |
| `resources/views/komunitas/kategori.blade.php` | List thread per kategori |
| `resources/views/komunitas/thread.blade.php` | Detail thread + replies + like/bookmark |
| `resources/views/komunitas/create.blade.php` | Form buat thread (judul, kategori, konten rich text, tag produk) |
| `resources/views/komunitas/leaderboard.blade.php` | Top 10 kontributor bulanan |
| `resources/views/akun/bookmarks.blade.php` | List thread yang di-bookmark |

**Thread detail layout:** Full-width, thread content di atas, replies di bawah sebagai nested cards.

**Rich text editor:** Gunakan `trix` (sudah di-include di Laravel default) atau Quill.js sederhana:
```html
<div id="editor" class="min-h-40 border rounded-xl p-4"></div>
<input type="hidden" name="konten" id="konten-input">
<script>
    // Jika gunakan Quill.js
    const quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: ['bold','italic','underline',{list:'bullet'},link] } });
    document.querySelector('form').addEventListener('submit', () => {
        document.getElementById('konten-input').value = quill.root.innerHTML;
    });
</script>
```

---

## SUB-STEP 3.3.7 — Filament: Forum Moderation

> 🔴 **Filament v5** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)): custom action di
> `Filament\Actions\Action`, bukan `Tables\Actions\Action`. Tiru `app/Filament/Resources/OrderResource.php`.

Di Filament Admin Store, buat `ForumModerationResource`:
- List semua thread (sortable by date, like_count, reply_count)
- Actions: Pin/Unpin thread, Hide thread, Delete thread
- Filter: per kategori, per status

```php
// Di dalam ->recordActions([ ... ]) (Filament v5):
\Filament\Actions\Action::make('pin')
    ->action(fn (ForumThread $r) => $r->update(['is_pinned' => !$r->is_pinned]))
    ->label(fn ($record) => $record->is_pinned ? 'Unpin' : 'Pin'),

\Filament\Actions\Action::make('hide')
    ->action(fn (ForumThread $r) => $r->update(['status' => $r->status === 'published' ? 'hidden' : 'published']))
    ->label(fn ($record) => $record->status === 'published' ? 'Hide' : 'Publish'),
```

---

## VERIFIKASI

```
1. Buka /komunitas → 5 kategori tampil, stats komunitas, thread (kosong awal)
2. Login → klik "Buat Thread" → isi judul, pilih kategori "Review Produk", isi konten
3. Submit → thread tampil di /komunitas/review-produk
4. Klik thread → halaman detail tampil
5. Post reply → reply tampil di bawah thread
6. Klik ♥ like pada thread → like_count naik (optimistic UI)
7. Klik 🔖 bookmark → /akun/bookmarks → thread tersimpan
8. Buka /akun/poin penulis thread → community_points naik +5
9. Buat 50 poin komunitas → Rising Star badge muncul
10. Admin Store login → /admin/store/forum-threads → bisa pin/hide
11. Mobile (375px) → kategori sebagai horizontal chips, thread list full-width
```

Setelah 3A + 3B + 3C selesai, lanjutkan ke **[phase-4-polish-testing.md](phase-4-polish-testing.md)**.
