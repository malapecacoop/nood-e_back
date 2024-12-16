<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class OrganizationCrudAuthTestWithImageTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createOrganizationWithImage($user): Organization
    {
        return Organization::create([
            'name' => 'Organization name',
            'image' => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'owner_id' => $user->id
        ]);
    }

    public function test_auth_admin_can_create_organization_with_image(): void
    {
        Storage::fake('public');

        $data = [
            'name' => 'Organization name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'owner_id' => $this->user->id
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/organizations', $data)
            ->assertCreated(201)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('image', fn ($image) => str($image)->contains('organization-'))
                    ->etc()
            );

        $image = Organization::first()->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($image);
    }

    public function test_auth_user_can_update_organization_with_image(): void
    {
        Storage::fake('public');

        $organization = $this->createOrganizationWithImage($this->user);
        $oldImage = $organization->image;

        $data = [
            'name' => 'Updated organization name', 
            "image" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGBgAAAABQABpfZFQAAAAABJRU5ErkJggg==",
            'owner_id' => $this->user->id
        ];

        $this->authenticated()
            ->put('/api/v1/organizations/'.$organization->id, $data)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('image', fn ($image) => str($image)->contains('organization-'))
                    ->etc()
            );

        $newImage = Organization::first()->image;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertExists($newImage);
        $storage->assertMissing($oldImage);
    }

    public function test_auth_user_can_delete_organization_with_image(): void
    {
        Storage::fake('public');

        $organization = $this->createOrganizationWithImage($this->user);

        $this->userRoleSuperAdmin()
            ->authenticated()
            ->delete('/api/v1/organizations/'.$organization->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Organization deleted successfully']
            );

        $image = $organization->image;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $storage->assertMissing($image);
    }
}
