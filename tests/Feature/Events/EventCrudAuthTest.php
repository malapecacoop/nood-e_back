<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class EventCrudAuthTest extends TestCase
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

    public function test_not_auth_user_cannot_get_all_events(): void
    {
        $this->authenticated('invalid-token')
            ->get('/api/v1/events')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_auth_user_can_get_all_events(): void
    {
        $room = $this->createRoom();
        $this->createEvent($room, $this->user);

        $this->authenticated()
            ->get('/api/v1/events')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([
                '*' => [
                    'title',
                    'description',
                    'start',
                    'end',
                    'meet_link',
                    'room_id',
                    'author_id'
                ]
            ]);
    }

    public function test_auth_user_can_list_owned_and_member_events(): void
    {
        $room = $this->createRoom();
        $event1 = $this->createEvent($room, $this->user);

        $user1 = User::factory()->create();
        $event2 = $this->createEvent($room, $user1);

        $event5 = $this->createEvent($room, $user1);
        $event5->members()->attach($this->user);

        $this->authenticated()
            ->get('/api/v1/events')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_list_events_date_filter_dont_show_more_than_two_months(): void
    {
        $room = $this->createRoom();
        
        $veryPastEvent = $this->createEvent($room, $this->user);
        $veryPastEvent->update(['start' => now()->subMonthsNoOverflow(3)]);
        $veryPastEvent->update(['end' => now()->subMonthsNoOverflow(3)->addHour()]);

        $veryFutureEvent = $this->createEvent($room, $this->user);
        $veryFutureEvent->update(['start' => now()->addMonthsNoOverflow(3)]);
        $veryFutureEvent->update(['end' => now()->addMonthsNoOverflow(3)->addHour()]);

        $pastMonthEvent = $this->createEvent($room, $this->user);
        $pastMonthEvent->update(['start' => now()->subMonthNoOverflow()]);
        $pastMonthEvent->update(['end' => now()->subMonthNoOverflow()->addHour()]);

        $todayEvent = $this->createEvent($room, $this->user);
        $todayEvent->update(['start' => now()]);
        $todayEvent->update(['end' => now()->addHour()]);

        $nextMonthEvent = $this->createEvent($room, $this->user);
        $nextMonthEvent->update(['start' => now()->addMonthNoOverflow()]);
        $nextMonthEvent->update(['end' => now()->addMonthNoOverflow()->addHour()]);

        // without dates, show range is from beginning of the month + 2 months
        $this->authenticated()
            ->get('/api/v1/events')
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('0.id', $todayEvent->id)
                ->where('1.id', $nextMonthEvent->id)
                ->etc()
            );

        // with start date, show range is from start date + 2 months
        $this->authenticated()
            ->get('/api/v1/events?start=' . now()->subMonthNoOverflow(1)->startOfMonth()->format('Y-m-d'))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('0.id', $pastMonthEvent->id)
                ->where('1.id', $todayEvent->id)
                ->etc()
            );

        // with start and end date, show range is from start date to end date
        $this->authenticated()
            ->get('/api/v1/events?start=' . now()->subMonthNoOverflow(1)->startOfMonth()->format('Y-m-d') . '&end=' . now()->subMonthNoOverflow(1)->endOfMonth()->format('Y-m-d'))
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('0.id', $pastMonthEvent->id)
                ->etc()
            );
    }

    public function test_auth_user_can_list_all_events_that_use_rooms_even_if_not_owned(): void
    {
        $room = $this->createRoom();
        $event1 = $this->createEvent($room, $this->user);
        
        $user1 = User::factory()->create();
        $event2 = $this->createEvent($room, $user1);

        $this->authenticated()
            ->get('/api/v1/events/rooms')
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('0.id', $event1->id)
                ->where('1.id', $event2->id)
                ->etc()
            );
    }

    public function test_get_room_events_date_filter(): void
    {
        $room = $this->createRoom();
        $event1 = $this->createEvent($room, $this->user);
        $event1->update(['start' => now()->subMonth()]);
        $event1->update(['end' => now()->subMonth()->addHour()]);

        $event2 = $this->createEvent($room, $this->user);

        $event3 = $this->createEvent($room, $this->user);
        $event3->update(['start' => now()->addMonth()]);
        $event3->update(['end' => now()->addMonth()->addHour()]);

        // TODO: this test is not working

        // $this->authenticated()
        //     ->get("/api/v1/rooms/{$room->id}")
        //     ->assertStatus(200)
        //     ->assertJsonCount(1)
        //     ->assertJson(fn (AssertableJson $json) => $json
        //         ->where('events', fn ($json) => $json
        //             ->count(1)
        //             ->etc()
        //         )->etc()
        //     );

        // $this->authenticated()
        //     ->get("/api/v1/rooms/{$room->id}?start=" . now()->subMonth()->startOfMonth()->format('Y-m-d'))
        //     ->assertStatus(200)
        //     ->assertJsonCount(1)
        //     ->assertJson(fn (AssertableJson $json) => $json
        //         ->where('events', fn ($json) => $json
        //             ->count(1)
        //             ->etc()
        //         )->etc()
        //     );

        // $this->authenticated()
        //     ->get("/api/v1/rooms/{$room->id}?start=" . now()->addMonth()->startOfMonth()->format('Y-m-d') . '&end=' . now()->addMonth()->endofMonth()->format('Y-m-d'))
        //     ->assertStatus(200)
        //     ->assertJsonCount(1)
        //     ->assertJson(fn (AssertableJson $json) => $json
        //         ->where('events', fn ($json) => $json
        //             ->count(1)
        //             ->etc()
        //         )->etc()
        //     );
    }

    public function test_auth_user_can_get_free_rooms(): void
    {
        $room1 = $this->createRoom($this->user);
        $room2 = $this->createRoom($this->user);
        $room2->update(['is_available' => false]);
        $room3 = $this->createRoom($this->user);
        $event = $this->createEvent($room3, $this->user);
        $event->update(['start' => now()->addDay()->setHour(12)->setMinute(0)->setSecond(0)]);
        $event->update(['end' => now()->addDay()->setHour(13)->setMinute(0)->setSecond(0)]);
        $room4 = $this->createRoom($this->user);
        $event = $this->createEvent($room4, $this->user);
        $event->update(['start' => now()->addDay()->setHour(13)->setMinute(0)->setSecond(0)]);
        $event->update(['end' => now()->addDay()->setHour(14)->setMinute(0)->setSecond(0)]);

        $this->authenticated()
            ->get('/api/v1/rooms/free?start='.now()->addDay()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s')
                .'&end=' . now()->addDay()->setHour(13)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'))
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'description',
                    'image',
                    'is_available',
                    'updated_at',
                    'created_at',
            ]]);
    }

    public function test_auth_user_can_get_event_by_id(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);

        $this->authenticated()
            ->get("/api/v1/events/{$event->id}")
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Event title',
                'description' => 'Event description',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'meet_link' => 'https://meet.google.com/abc-def-ghi',
                'room_id' => $room->id,
                'author_id' => $this->user->id
            ]);
    }

    public function test_auth_user_can_create_event_only_required_fields(): void
    {
        $this->authenticated()
            ->post('/api/v1/events', [
                'title' => 'Event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
            ])
            ->assertCreated()
            ->assertJson([
                'title' => 'Event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'author_id' => $this->user->id
            ]);
    }

    public function test_update_event_only_required_fields(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);

        $this->authenticated()
            ->put("/api/v1/events/{$event->id}", [
                'title' => 'Updated event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
            ])->assertStatus(200)
            ->assertJson([
                'title' => 'Updated event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'author_id' => $this->user->id
            ]);
    }

    public function test_auth_user_cannot_update_event_not_authored(): void
    {
        $room = $this->createRoom();
        
        $user = User::factory()->create();
        $event = $this->createEvent($room, $user);

        $this->authenticated()
            ->put("/api/v1/events/{$event->id}", [
                'title' => 'Updated event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_event_not_authored(): void
    {
        $room = $this->createRoom();
        
        $user = User::factory()->create();
        $event = $this->createEvent($room, $user);

        $this->userRoleAdmin()
            ->authenticated()
            ->put("/api/v1/events/{$event->id}", [
                'title' => 'Updated event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Updated event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->toISOString(),
                'author_id' => $user->id
            ]);
    }


    public function test_auth_user_can_create_event_with_members(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->authenticated()
            ->post('/api/v1/events', [
                'title' => 'Event title',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'members' => [$user1->id, $user2->id]
            ])
            ->assertCreated(201)
            ->assertJson([
                'title' => 'Event title',
                'author_id' => $this->user->id,
                'members' => [
                    ['id' => $user1->id, 'name' => $user1->name],
                    ['id' => $user2->id, 'name' => $user2->name]
                ]
            ]);
    }

    public function test_auth_user_can_update_event_with_members(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->authenticated()
            ->put("/api/v1/events/{$event->id}", [
                'title' => 'Event title updated',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'members' => [$user1->id, $user2->id]
            ])
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Event title updated',
                'author_id' => $this->user->id,
                'members' => [
                    ['id' => $user1->id, 'name' => $user1->name],
                    ['id' => $user2->id, 'name' => $user2->name]
                ]
            ]);

        $this->authenticated()
            ->put("/api/v1/events/{$event->id}", [
                'title' => 'Event title updated',
                'start' => now()->setHour(12)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'end' => now()->setHour(14)->setMinute(0)->setSecond(0)->setMillisecond(0)->format('Y-m-d H:i:s'),
                'members' => [$user1->id]
            ])
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('members', 1)
                ->missing($user2->name)
                ->etc()
            );
    }

    public function test_auth_user_can_delete_event(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);

        $this->authenticated()
            ->delete("/api/v1/events/{$event->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Event deleted successfully']);
    }

    public function test_auth_user_cannot_delete_event_not_authored(): void
    {
        $room = $this->createRoom();
        $user = User::factory()->create();
        $event = $this->createEvent($room, $user);

        $this->authenticated()
            ->delete("/api/v1/events/{$event->id}")
            ->assertStatus(403);
    }

    public function test_auth_admin_can_delete_event_not_authored(): void
    {
        $room = $this->createRoom();
        $user = User::factory()->create();
        $event = $this->createEvent($room, $user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/events/{$event->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Event deleted successfully']);
    }

    public function test_room_can_be_deleted_if_it_has_past_events(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);
        $event->update(['start' => now()->subDay()]);
        $event->update(['end' => now()->subDay()->addHour()]);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/rooms/{$room->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Room deleted successfully']);

        $this->assertNull((Event::find($event->id))->room_id);
    }

    public function test_room_cannot_be_deleted_if_it_has_future_events(): void
    {
        $room = $this->createRoom();
        $event = $this->createEvent($room, $this->user);
        $event->update(['start' => now()->addDay()]);
        $event->update(['end' => now()->addDay()->addHour()]);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/rooms/{$room->id}")
            ->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete room with future events']);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id
        ]);
    }

}
