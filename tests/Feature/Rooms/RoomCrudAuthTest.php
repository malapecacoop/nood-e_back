<?php

namespace Tests\Feature\Rooms;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Authentication;
use Tests\TestCase;

class RoomCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createRoom (): Room
    {
        return Room::create([
            'name' => 'Room name',
            'description' => 'Room description',
            'is_available' => true,
        ]);
    }

    public function test_not_auth_user_cannot_get_all_rooms(): void
    {
        $this->authenticated('invalid-token')
            ->get('/api/v1/rooms')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_auth_user_can_get_all_available_rooms(): void
    {
        // reset auto-increment ID for rooms table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('rooms')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $room1 = $this->createRoom($this->user);
        $room2 = $this->createRoom($this->user);
        $room2->update(['is_available' => false]);

        $this->authenticated()
            ->get('/api/v1/rooms')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
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

    public function test_auth_user_can_get_room_by_id(): void
    {
        $room = $this->createRoom($this->user);

        $this->authenticated()
            ->get('/api/v1/rooms/'.$room->id)
            ->assertJson([
                'name' => 'Room name',
                'description' => 'Room description',
                'is_available' => true,
            ])
            ->assertStatus(200);
    }

    public function test_auth_admin_can_create_room_only_required_fields()
    {
        $data = [
            'name' => 'Room name',
        ];
        
        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/rooms', $data)
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Room name',
            ]);
    }

    public function test_auth_user_cannot_create_room()
    {
        $data = [
            'name' => 'Room name',
        ];

        $this->authenticated()
            ->post('/api/v1/rooms', $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_room_only_required_fields()
    {
        $room = $this->createRoom();

        $data = [
            'name' => 'Updated room name',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put('/api/v1/rooms/'.$room->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated room name',
            ]);
    }

    public function test_auth_user_cannot_update_room()
    {
        $room = $this->createRoom();

        $data = [
            'name' => 'Updated room name',
        ];

        $this->authenticated()
            ->put('/api/v1/rooms/'.$room->id, $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_delete_room()
    {
        $room = $this->createRoom();

        $this->userRoleAdmin()
            ->authenticated()
            ->delete('/api/v1/rooms/'.$room->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Room deleted successfully']
            );
    }

    public function test_auth_user_cannot_delete_room()
    {
        $room = $this->createRoom();

        $this->authenticated()
            ->delete('/api/v1/rooms/'.$room->id)
            ->assertStatus(403);
    }
}