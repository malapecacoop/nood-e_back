<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy extends Policy
{
    public function create(User $authUser)
    {
        return $authUser->isAdmin();
    }

    public function update(User $authUser, Organization $organization)
    {
        return $authUser->isAdmin() || $organization->owner_id === $authUser->id;
    }

    public function delete(User $authUser, Organization $organization)
    {
        return false;
    }
}
