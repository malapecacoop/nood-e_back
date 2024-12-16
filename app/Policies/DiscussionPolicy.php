<?php

namespace App\Policies;

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DiscussionPolicy extends Policy
{
    public function view(User $authUser, Discussion $discussion)
    {
        if (
                $discussion->is_public ||
                $authUser->isAdmin() ||
                $discussion->author_id === $authUser->id ||
                $discussion->members->contains($authUser)
        ) {
            return Response::allow();
        } else {
            return Response::denyAsNotFound();
        }
    }

    public function update(User $authUser, Discussion $discussion)
    {
        return $discussion->author_id === $authUser->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, Discussion $discussion)
    {
        return $discussion->author_id === $authUser->id || $authUser->isAdmin();
    }
}
