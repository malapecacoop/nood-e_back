<?php

namespace App\Observers;

use App\Models\Discussion;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiscussionObserver
{
    public function deleting(Discussion $discussion)
    {
        if ($discussion->topics()->count()) {
            throw new HttpException(409, 'Cannot delete discussion with topics');
        }
    }
}
