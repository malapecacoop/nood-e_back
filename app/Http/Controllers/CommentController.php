<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Topic;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Discussion $discussion, Topic $topic)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        $data = $request->validated();
        $data['author_id'] = $this->user->id;
        $data['topic_id'] = $topic->id;

        $comment = Comment::create($data);

        return response()->json($comment, 201);
    }

    public function update(CommentRequest $request, Discussion $discussion, Topic $topic, Comment $comment)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        if ($comment->topic_id !== $topic->id) {
            return response()->json(['error' => 'Comment not found in this topic'], 404);
        }

        Gate::authorize('update', $comment);

        $data = $request->validated();

        $comment->update($data);
        return response()->json($comment, 200);
    }

    public function destroy(Discussion $discussion, Topic $topic, Comment $comment)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        if ($comment->topic_id !== $topic->id) {
            return response()->json(['error' => 'Comment not found in this topic'], 404);
        }

        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully'], 200);
    }
}
