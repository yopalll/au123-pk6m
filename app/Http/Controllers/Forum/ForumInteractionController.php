<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumBookmark;
use App\Models\ForumLike;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Services\BadgeService;

class ForumInteractionController extends Controller
{
    public function likeThread(ForumThread $thread)
    {
        $existing = ForumLike::where('id_user', auth()->id())
            ->where('likeable_type', 'forum_thread')->where('likeable_id', $thread->id_thread)->first();

        if ($existing) {
            $existing->delete();
            $thread->decrement('like_count');
            $liked = false;
        } else {
            ForumLike::create(['id_user' => auth()->id(), 'likeable_type' => 'forum_thread', 'likeable_id' => $thread->id_thread]);
            $thread->increment('like_count');
            $liked = true;

            // +2 poin ke penulis thread (dapat like)
            if ($thread->id_user !== auth()->id()) {
                ForumController::awardPoints($thread->id_user, 2);
                BadgeService::check($thread->id_user);
            }
        }

        return response()->json(['liked' => $liked, 'like_count' => $thread->fresh()->like_count]);
    }

    public function likeReply(ForumReply $reply)
    {
        $existing = ForumLike::where('id_user', auth()->id())
            ->where('likeable_type', 'forum_reply')->where('likeable_id', $reply->id_reply)->first();

        if ($existing) {
            $existing->delete();
            $reply->decrement('like_count');
            $liked = false;
        } else {
            ForumLike::create(['id_user' => auth()->id(), 'likeable_type' => 'forum_reply', 'likeable_id' => $reply->id_reply]);
            $reply->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $reply->fresh()->like_count]);
    }

    public function bookmark(ForumThread $thread)
    {
        $existing = ForumBookmark::where('id_user', auth()->id())->where('id_thread', $thread->id_thread)->first();

        if ($existing) {
            $existing->delete();
            $bookmarked = false;
        } else {
            ForumBookmark::create(['id_user' => auth()->id(), 'id_thread' => $thread->id_thread]);
            $bookmarked = true;
        }

        return response()->json(['bookmarked' => $bookmarked]);
    }

    public function bookmarks()
    {
        $bookmarks = ForumBookmark::where('id_user', auth()->id())
            ->with(['thread.user', 'thread.category'])
            ->latest()->paginate(15);

        return view('akun.bookmarks', compact('bookmarks'));
    }
}
