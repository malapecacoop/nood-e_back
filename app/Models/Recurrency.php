<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurrency extends Model
{
    use HasFactory;

    const TYPE_DAY = 1;
    const TYPE_WEEK = 2;
    const TYPE_MONTH = 3;
    const TYPE_YEAR = 4;

    const DAYS_GENERATE = 547; // 1.5 years

    protected $fillable = [
        'type',
        'end',
    ];

    protected $casts = [
        'end' => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function firstEvent()
    {
        return $this->belongsTo(Event::class);
    }

    public function generateEvents()
    {
        // if just created, generate events from the first event date
        if ($this->events()->count() == 1) {
            $fromEvent = $this->firstEvent;
        }
        // if there are already events created, we are updating, generate events from the last event date
        else {
            $fromEvent = $this->events()->orderBy('start', 'desc')->first();
        }

        $dateRecurrencyEnd = $this->end;
        $maxRecurrencyDate = Carbon::now()->addDays(Recurrency::DAYS_GENERATE);
        if (!$dateRecurrencyEnd || $dateRecurrencyEnd->isAfter($maxRecurrencyDate)) {
            $dateRecurrencyEnd = $maxRecurrencyDate;
        }
        $dateRecurrencyEnd->addDay();

        $addPeriodMethod = self::getCarbonMethod($this->type);

        $events = [];

        $dateStart = $fromEvent->start;
        $dateEnd = $fromEvent->end;
        $dateStart->$addPeriodMethod();
        $dateEnd->$addPeriodMethod();

        while ($dateStart->isBefore($dateRecurrencyEnd)) {
            $events[] = [
                'title' => $fromEvent->title,
                'description' => $fromEvent->description,
                'meet_link' => $fromEvent->meet_link,
                'author_id' => $fromEvent->author_id,
                'room_id' => $fromEvent->room_id,
                'start' => $dateStart->format('Y-m-d H:i:s'), //this is needed to freeze the date (because the object $dateStart changes in each iteration)
                'end' => $dateEnd->format('Y-m-d H:i:s'), // idem
                'recurrency_id' => $this->id,
            ];

            $dateStart->$addPeriodMethod();
            $dateEnd->$addPeriodMethod();
        }
        $this->events()->createMany($events);

        $members = $fromEvent->members->pluck('id')->toArray();
        foreach ($this->events as $event) {
            $event->members()->sync($members);
        }
    }

    public static function getCarbonMethod($type)
    {
        switch ($type) {
            case Recurrency::TYPE_DAY:
                return 'addDay';
            case Recurrency::TYPE_WEEK:
                return 'addWeek';
            case Recurrency::TYPE_MONTH:
                return 'addMonthNoOverflow';
            case Recurrency::TYPE_YEAR:
                return 'addYear';
            default:
                throw new \InvalidArgumentException('Invalid recurrency type');
        }
    }
}
