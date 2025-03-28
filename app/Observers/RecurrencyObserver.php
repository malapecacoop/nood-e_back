<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Recurrency;

class RecurrencyObserver
{
    public function updated(Recurrency $recurrency)
    {
        if ($recurrency->isDirty('first_event_id') && $recurrency->first_event_id) {
            $recurrency->generateEvents();
        }

        if ($recurrency->isDirty('end')) {
            $oldEnd = $recurrency->getOriginal('end');
            $newEnd = $recurrency->end ? $recurrency->end->copy() : null;

            if (!$oldEnd || ($newEnd && $newEnd->isBefore($oldEnd))) {
                Event::where('recurrency_id', $recurrency->id)
                    ->where('start', '>', $newEnd->addDay())
                    ->delete();
            } else {
                $recurrency->generateEvents();
            }
        }
    }    
}
