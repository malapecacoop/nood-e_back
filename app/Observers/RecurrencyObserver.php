<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Recurrency;
use Carbon\Carbon;

class RecurrencyObserver
{
    public function updated(Recurrency $recurrency)
    {
        if ($recurrency->isDirty('first_event_id') && $recurrency->first_event_id) {
            $dateRecurrencyEnd = $recurrency->end;
            $maxRecurrencyDate = Carbon::now()->addDays(Recurrency::DAYS_GENERATE);
            if (!$dateRecurrencyEnd || $dateRecurrencyEnd->isAfter($maxRecurrencyDate)) {
                $dateRecurrencyEnd = $maxRecurrencyDate;
            }

            $dateRecurrencyEnd->addDay();

            $this->generateEvents($recurrency, $recurrency->firstEvent, $dateRecurrencyEnd);
        }

        if ($recurrency->isDirty('end')) {
            $oldEnd = $recurrency->getOriginal('end');
            $dateRecurrencyEnd = $recurrency->end;
            $maxRecurrencyDate = Carbon::now()->addDays(Recurrency::DAYS_GENERATE);
            if (!$dateRecurrencyEnd || $dateRecurrencyEnd->isAfter($maxRecurrencyDate)) {
                $dateRecurrencyEnd = $maxRecurrencyDate;
            }
            $dateRecurrencyEnd->addDay();
            if (!$oldEnd || $oldEnd->isAfter($maxRecurrencyDate)) {
                $oldEnd = $maxRecurrencyDate;
            }
            $oldEnd->addDay();

            if ($dateRecurrencyEnd->isBefore($oldEnd)) {
                Event::where('recurrency_id', $recurrency->id)
                    ->where('start', '>=', $dateRecurrencyEnd)
                    ->delete();
            } else {
                $lastEvent = Event::where('recurrency_id', $recurrency->id)
                    ->orderBy('start', 'desc')
                    ->first();

                $this->generateEvents($recurrency, $lastEvent, $dateRecurrencyEnd);
            }
        }
    }

    private function generateEvents(
        Recurrency $recurrency,
        Event $event,
        Carbon $recurrencyEnd
    ): void
    {
        switch ($recurrency->type) {
            case Recurrency::TYPE_DAY:
                $addPeriodFunction = 'addDay';
                break;
            case Recurrency::TYPE_WEEK:
                $addPeriodFunction = 'addWeek';
                break;
            case Recurrency::TYPE_MONTH:
                $addPeriodFunction = 'addMonthNoOverflow';
                break;
            case Recurrency::TYPE_YEAR:
                $addPeriodFunction = 'addYear';
                break;
            default:
                throw new \InvalidArgumentException('Invalid recurrency type');
        }

        $events = [];

        $dateStart = $event->start;
        $dateEnd = $event->end;

        $dateStart->$addPeriodFunction();
        $dateEnd->$addPeriodFunction();

        while ($dateStart->isBefore($recurrencyEnd)) {
            $events[] = [
                'title' => $event->title,
                'description' => $event->description,
                'meet_link' => $event->meet_link,
                'author_id' => $event->author_id,
                'room_id' => $event->room_id,
                'start' => $dateStart->format('Y-m-d H:i:s'),
                'end' => $dateEnd->format('Y-m-d H:i:s'),
                'recurrency_id' => $recurrency->id,
            ];
            $dateStart->$addPeriodFunction();
            $dateEnd->$addPeriodFunction();
        }

        $recurrency->events()->createMany($events);

        $members = $event->members->pluck('id')->toArray();
        foreach ($recurrency->events as $event) {
            $event->members()->sync($members);
        }
    }
    
}
