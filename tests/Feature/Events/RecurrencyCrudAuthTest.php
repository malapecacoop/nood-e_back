<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\Recurrency;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class RecurrencyCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createEvent($room, $user): Event
    {
        return Event::create([
            'title' => 'Event title',
            'description' => 'Event description',
            'start' => now()->setHour(12)->setMinute(0)->setSecond(0),
            'end' => now()->setHour(14)->setMinute(0)->setSecond(0),
            'meet_link' => 'https://meet.google.com/abc-def-ghi',
            'room_id' => $room->id,
            'author_id' => $user->id,
        ]);
    }

    private function createRoom(): Room
    {
        return Room::create([
            'name' => 'Room 1',
            'description' => 'Room 1 description',
            'is_available' => true
        ]);
    }

    public function test_auth_user_can_create_event_with_recurrency(): void
    {
        $this->authenticated()
            ->post('/api/v1/events', [
                'title' => 'Event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'recurrency_type' => Recurrency::TYPE_DAY,
                'recurrency_end' => now()->addDays(10)->format('Y-m-d'),
            ])
            ->assertCreated(201)
            ->assertJson([
                'title' => 'Event title',
                'author_id' => $this->user->id,
                'recurrency_id' => 1,
            ]);
    }

    public function test_event_with_recurrency_cannot_be_created_if_room_is_not_available_for_any_event_in_recurrency(): void
    {
        $room = $this->createRoom();
        $event1 = $this->createEvent($room, $this->user);
        $event1->update([
            'start' => now()->addDays(5)->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
            'end' => now()->addDays(5)->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
        ]);

        // Create a new event with recurrency in an available room
        $this->authenticated()
            ->post('/api/v1/events', [
                'title' => 'Event title',
                'start' => now()->setHour(11)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'recurrency_type' => Recurrency::TYPE_DAY,
                'recurrency_end' => now()->addDays(10)->format('Y-m-d'),
                'room_id' => $room->id,
            ])
            ->assertCreated(201)
            ->assertJson([
                'title' => 'Event title',
                'author_id' => $this->user->id,
            ]);

        // Create a new event with recurrency in an unavailable room
        $this->authenticated()
            ->post('/api/v1/events', [
                'title' => 'Event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(13)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'recurrency_type' => Recurrency::TYPE_DAY,
                'recurrency_end' => now()->addDays(10)->format('Y-m-d'),
                'room_id' => $room->id,
            ])
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Room is not available for some dates in the recurrency',
            ]);
    }

}
