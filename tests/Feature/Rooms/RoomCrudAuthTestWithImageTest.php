<?php

namespace Tests\Feature\Rooms;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class RoomCrudAuthTestWithImageTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createRoomWithImage($user): Room
    {
        return Room::create([
            'name' => 'Room name',
            'image' => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
        ]);
    }

    public function test_auth_admin_can_create_room_with_image(): void
    {
        Storage::fake('public');

        $data = [
            'name' => 'Room name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/rooms', $data)
            ->assertCreated(201)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('image', fn ($image) => str($image)->contains('room-'))
                    ->etc()
            );

        $image = Room::first()->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($image);
    }

    public function test_auth_admin_can_update_room_with_image(): void
    {
        Storage::fake('public');

        $room = $this->createRoomWithImage($this->user);
        $oldImage = $room->image;

        $data = [
            'name' => 'Updated room name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put('/api/v1/rooms/'.$room->id, $data)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('image', fn ($image) => str($image)->contains('room-'))
                    ->etc()
            );

        $newImage = Room::first()->image;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($newImage);
        $storage->assertMissing($oldImage);
    }

    public function test_auth_admin_can_delete_room_with_image(): void
    {
        Storage::fake('public');

        $room = $this->createRoomWithImage($this->user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete('/api/v1/rooms/'.$room->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Room deleted successfully']
            );

        $image = $room->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertMissing($image);
    }
}
