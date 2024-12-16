<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy extends Policy
{
    public function update(User $authUser, Comment $comment)
    {
        return $comment->author_id === $authUser->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, Comment $comment)
    {
        return $comment->author_id === $authUser->id || $authUser->isAdmin();
    }
}
