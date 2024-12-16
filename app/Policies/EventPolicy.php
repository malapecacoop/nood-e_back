<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy extends Policy
{
    public function update(User $authUser, Event $event)
    {
        return $event->author_id === $authUser->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, Event $event)
    {
        return $event->author_id === $authUser->id || $authUser->isAdmin();
    }
}
