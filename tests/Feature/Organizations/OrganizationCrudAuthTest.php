<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class OrganizationCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createOrganization($user): Organization
    {
        return Organization::create([
            'name' => 'Organization name',
            'description' => 'Organization description',
            'owner_id' => $user->id
        ]);
    }

    public function test_not_auth_user_cannot_get_all_organizations(): void
    {
        $this->authenticated('invalid-token')
            ->get('/api/v1/organizations')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_auth_user_can_get_all_organizations(): void
    {
        $this->createOrganization($this->user);

        $this->authenticated()
            ->get('/api/v1/organizations')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'description',
                    'image',
                    'owner_id',
                    'updated_at',
                    'created_at',
            ]]);
    }

    public function test_auth_user_can_get_organization_by_id(): void
    {
        $organization = $this->createOrganization($this->user);

        $this->authenticated()
            ->get('/api/v1/organizations/'.$organization->id)
            ->assertJson([
                'name' => 'Organization name',
                'description' => 'Organization description',
                'owner_id' => $this->user->id
            ])
            ->assertStatus(200);
    }

    public function test_auth_user_cannot_create_organization(): void
    {
        $data = [
            'name' => 'Organization name', 
            'owner_id' => $this->user->id
        ];

        $this->authenticated()
            ->post('/api/v1/organizations', $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_create_organization_only_required_fields(): void
    {
        $data = [
            'name' => 'Organization name', 
            'owner_id' => $this->user->id
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/organizations', $data)
            ->assertCreated(201)
            ->assertJsonFragment([
                'name' => 'Organization name', 
                'owner_id' => $this->user->id
        ]);
    }

    public function test_auth_user_can_update_organization_only_required_fields(): void
    {
        $organization = $this->createOrganization($this->user);

        $data = [
            'name' => 'Updated organization name', 
            'owner_id' => $this->user->id
        ];

        $this->authenticated()
            ->put('/api/v1/organizations/'.$organization->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated organization name', 
                'owner_id' => $this->user->id
            ]);
    }

    public function test_auth_user_cannot_update_not_owned_organization(): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganization($user);

        $data = [
            'name' => 'Updated organization name', 
            'owner_id' => $user->id
        ];

        $this->authenticated()
            ->put('/api/v1/organizations/'.$organization->id, $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_not_owned_organization(): void
    {
        $user = User::factory()->create();
        $organization = $this->createOrganization($user);

        $data = [
            'name' => 'Updated organization name', 
            'owner_id' => $user->id
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put('/api/v1/organizations/'.$organization->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated organization name', 
                'owner_id' => $user->id
            ]);
    }

    public function test_auth_user_cannot_delete_organization(): void
    {
        $organization = $this->createOrganization($this->user);

        $this->authenticated()
            ->delete('/api/v1/organizations/'.$organization->id)
            ->assertStatus(403);
    }

    public function test_auth_superadmin_can_delete_organization(): void
    {
        $organization = $this->createOrganization($this->user);

        $this->userRoleSuperAdmin()
            ->authenticated()
            ->delete('/api/v1/organizations/'.$organization->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Organization deleted successfully']
            );
    }

    public function test_user_organization_id_is_nulled_when_organization_is_deleted(): void
    {
        $organization = $this->createOrganization($this->user);
        $user = User::factory()->create();
        $user->organization_id = $organization->id;
        $user->save();

        $this->userRoleSuperAdmin()
            ->authenticated()
            ->delete('/api/v1/organizations/'.$organization->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Organization deleted successfully']
            );

        $this->assertNull(User::find($user->id)->organization_id);
    }
}
