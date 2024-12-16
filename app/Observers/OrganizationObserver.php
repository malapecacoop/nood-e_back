<?php

namespace App\Observers;

use App\Models\Organization;

class OrganizationObserver
{
    public function deleted(Organization $organization)
    {
        if ($organization->image) {
            $organization->deleteImage($organization->image);
        }
    }
}
