<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Models\Discussion;
use App\Models\Topic;
use Illuminate\Support\Facades\Gate;

class TopicController extends Controller
{
    public function store(TopicRequest $request, Discussion $discussion, Topic $topic)
    {
        $data = $request->validated();
        $data['author_id'] = $this->user->id;
        $data['discussion_id'] = $discussion->id;
        $data['last_update'] = now();

        $topic = Topic::create($data);

        return response()->json($topic, 201);
    }

    public function show(Discussion $discussion, Topic $topic)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        $topic->load(['author', 'comments.author', 'discussion']);

        return response()->json($topic, 200);
    }

    public function update(TopicRequest $request, Discussion $discussion, Topic $topic)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        Gate::authorize('update', $topic);

        $data = $request->validated();
        $topic->update($data);
        return response()->json($topic, 200);
    }

    public function destroy(Discussion $discussion, Topic $topic)
    {
        if ($topic->discussion_id !== $discussion->id) {
            return response()->json(['error' => 'Topic not found in this discussion'], 404);
        }

        Gate::authorize('delete', $topic);

        $topic->delete();

        return response()->json(['message' => 'Topic deleted successfully'], 200);
    }
}
