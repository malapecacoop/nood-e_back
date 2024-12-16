<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class UserCrudAuthTestWithImageTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createUserWithImage($user): User
    {
        return User::create([
            'name' => 'User name',
            'image' => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'email' => 'test@test.com',
        ]);
    }

    public function test_auth_user_can_create_user_with_image(): void
    {
        Storage::fake('public');

        $data = [
            'name' => 'User name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'email' => 'test@test.com',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/users', $data)
            ->assertCreated(201)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('user.image', fn ($image) => str($image)->contains('user-'))
                    ->etc()
            );

        $image = User::first()->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($image);
    }

    public function test_auth_user_can_update_user_with_image(): void
    {
        Storage::fake('public');

        $this->user->image = "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==";
        $this->user->save();

        $oldImage = $this->user->image;

        $data = [
            'name' => 'Updated user name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'email' => 'updated@test.com',
        ];

        $this->authenticated()
            ->put('/api/v1/users/'.$this->user->id, $data)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('image', fn ($image) => str($image)->contains('user-'))
                    ->etc()
            );

        $newImage = User::first()->image;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($newImage);
        $storage->assertMissing($oldImage);
    }

    public function test_auth_admin_can_delete_user_with_image(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithImage($this->user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete('/api/v1/users/'.$user->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'User deactivated successfully']
            );

        $image = $user->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($image);
    }
}
