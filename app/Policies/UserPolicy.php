<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends Policy
{
    public function massInvite(User $authUser)
    {
        return $authUser->isAdmin();
    }

    public function create(User $authUser)
    {
        return $authUser->isAdmin();
    }

    public function update(User $authUser, User $user)
    {
        return $user->id === $authUser->id;
    }

    public function delete(User $authUser, User $user)
    {
        return $authUser->isAdmin();
    }

    public function restore(User $authUser, User $user)
    {
        return $authUser->isAdmin();
    }

}
