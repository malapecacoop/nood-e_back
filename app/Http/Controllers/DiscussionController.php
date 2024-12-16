<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscussionRequest;
use App\Models\Discussion;
use Illuminate\Support\Facades\Gate;

class DiscussionController extends Controller
{    
    public function index()
    {
        $discussions = Discussion::where('is_public', true)->orWhere(
            function ($query) {
                $query->where('is_public', false)
                    ->where('author_id', $this->user->id)
                    ->orWhereHas('members', fn($query) => $query->where('user_id', $this->user->id));
            }
        )->get();

        return response()->json($discussions, 200);
    }

    public function store(DiscussionRequest $request, Discussion $discussion)
    {
        $data = $request->validated();

        $data['author_id'] = $this->user->id;

        $members = $this->getMembersFromData($data);

        $discussion = Discussion::create($data);

        $discussion = $this->attachMembers($discussion, $members);

        return response()->json($discussion, 201);
    }

    public function show(Discussion $discussion)
    {
        Gate::authorize('view', $discussion);

        $discussion->load('topics', 'members');

        return response()->json($discussion, 200);
    }

    public function update(DiscussionRequest $request, Discussion $discussion)
    {
        Gate::authorize('update', $discussion);

        $data = $request->validated();

        $members = $this->getMembersFromData($data);

        $discussion->update($data);

        $discussion = $this->attachMembers($discussion, $members);

        return response()->json($discussion, 200);
    }

    public function destroy(Discussion $discussion)
    {
        Gate::authorize('delete', $discussion);

        $discussion->delete();

        return response()->json(['message' => 'Discussion deleted successfully'], 200);
    }

    private function getMembersFromData(array &$data): array
    {
        $members = $data['members'] ?? [];
        unset($data['members']);

        return $members;
    }

    private function attachMembers(Discussion $discussion, array $members): Discussion
    {
        $discussion->members()->sync($members);
        $discussion->load('members');

        return $discussion;
    }
}
