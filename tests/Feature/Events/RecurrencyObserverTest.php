<?php

namespace Tests\Feature\Comments;

use App\Models\Event;
use App\Models\Recurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class RecurrencyObserverTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createEvent($user, $recurrency): Event
    {
        return Event::create([
            'title' => 'Event title',
            'description' => 'Event description',
            'start' => now()->setHour(12)->setMinute(0)->setSecond(0),
            'end' => now()->setHour(14)->setMinute(0)->setSecond(0),
            'author_id' => $user->id,
            'recurrency_id' => $recurrency->id,
        ]);
    }

    private function createRecurrency(): Recurrency
    {
        return Recurrency::create([
            'type' => 1,
            'end' => now()->addDays(10)->format('Y-m-d'),
        ]);
    }

    public function test_recurrent_events_are_generated_when_first_event_id_is_set()
    {
        $this->withoutExceptionHandling();

        $recurrency = $this->createRecurrency();
        $event = $this->createEvent($this->user, $recurrency);
        $recurrency->first_event_id = $event->id;
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(11, $recurrency->events);
    }

    public function test_recurrent_events_are_deleted_when_end_date_is_updated_to_a_date_before()
    {
        $recurrency = $this->createRecurrency();
        $event = $this->createEvent($this->user, $recurrency);
        $recurrency->first_event_id = $event->id;
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(11, $recurrency->events);

        $recurrency->end = now()->addDays(5)->format('Y-m-d');
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(6, $recurrency->events);
    }

    public function test_recurrent_events_are_added_when_end_date_is_updated_to_a_date_after()
    {
        $recurrency = $this->createRecurrency();
        $event = $this->createEvent($this->user, $recurrency);
        $recurrency->first_event_id = $event->id;
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(11, $recurrency->events);

        $recurrency->end = now()->addDays(15)->format('Y-m-d');
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(16, $recurrency->events);
    }
}
