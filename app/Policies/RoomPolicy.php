<?php

namespace App\Policies;

use App\Models\User;

class RoomPolicy extends Policy
{
    public function create(User $authUser)
    {
        return $authUser->isAdmin();
    }

    public function update(User $authUser)
    {
        return $authUser->isAdmin();
    }

    public function delete(User $authUser)
    {
        return $authUser->isAdmin();
    }
}
