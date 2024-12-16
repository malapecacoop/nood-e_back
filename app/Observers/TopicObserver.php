<?php

namespace App\Observers;

use App\Models\Topic;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TopicObserver
{
    public function deleting(Topic $topic)
    {
        if ($topic->comments()->count()) {
            throw new HttpException(409, 'Cannot delete topic with comments');
        }
    }
}
