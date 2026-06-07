<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumReply;
use App\Models\ForumThread;
use Illuminate\Http\Request;

class ForumReplyController extends Controller
{
    public function store(Request $request, ForumThread $thread)
    {
        abort_if($thread->status !== 'published' || $thread->is_locked, 403, 'Thread terkunci.');

        $request->validate([
            'konten' => 'required|string|min:3|max:5000',
            'parent_id' => 'nullable|exists:forum_replies,id_reply',
        ]);

        // Max 2 level nesting
        if ($request->parent_id) {
            $parent = ForumReply::find($request->parent_id);
            abort_if($parent && $parent->parent_id !== null, 422, 'Balasan maksimal 2 level.');
        }

        ForumReply::create([
            'id_thread' => $thread->id_thread,
            'id_user' => auth()->id(),
            'parent_id' => $request->parent_id,
            'konten' => clean($request->konten),
            'status' => 'published',
        ]);

        $thread->increment('reply_count');

        // +1 poin komunitas ke penulis thread (dapat reply)
        if ($thread->id_user !== auth()->id()) {
            ForumController::awardPoints($thread->id_user, 1);
        }

        return back()->with('success', 'Balasan terkirim!');
    }
}
