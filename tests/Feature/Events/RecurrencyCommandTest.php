<?php

namespace Tests\Feature\Comments;

use App\Models\Event;
use App\Models\Recurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class RecurrencyCommandTest extends TestCase
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

    public function test_recurrent_events_are_generated()
    {
        $recurrency = $this->createRecurrency();
        $event = $this->createEvent($this->user, $recurrency);
        $recurrency->first_event_id = $event->id;
        $recurrency->save();

        $recurrency->load('events');
        $this->assertCount(11, $recurrency->events);

        //remove the last event to test the command
        $recurrency->events()->orderBy('id', 'desc')->first()->delete();
        $recurrency->refresh();
        $this->assertCount(10, $recurrency->events);

        $this->artisan('recurrency:generate')
            ->expectsOutput('Generating recurring events...')
            ->expectsOutput('Recurring events generated successfully.')
            ->assertExitCode(0);
        $recurrency->refresh();
        $this->assertCount(11, $recurrency->events);
    }
}
