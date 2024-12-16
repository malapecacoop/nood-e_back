<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy extends Policy
{
    public function update(User $authUser, Topic $topic)
    {
        return $topic->author_id === $authUser->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, Topic $topic)
    {
        return $topic->author_id === $authUser->id || $authUser->isAdmin();
    }
}
