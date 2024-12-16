<?php

namespace App\Observers;

use App\Models\Room;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomObserver
{
    public function deleting(Room $room)
    {
        if ($room->events()->where('start', '>=', now())->exists()) {
            throw new HttpException(409, 'Cannot delete room with future events');
        }
    }

    public function deleted(Room $room)
    {
        if ($room->image) {
            $room->deleteImage($room->image);
        }
    }
}
