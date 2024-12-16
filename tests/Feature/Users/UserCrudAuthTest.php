<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

use function PHPUnit\Framework\assertJson;

class UserCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createUser(): User
    {
        return User::create([
            'name' => 'User name',
            'surname' => 'User surname',
            'description' => 'User description',
            'email' => 'test@test.com',
            'invite_accepted_at' => now(),
        ]);
    }

    public function test_not_auth_user_cannot_get_all_users(): void
    {
        $this->authenticated('invalid-token')
            ->get('/api/v1/users')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_auth_user_can_get_all_users(): void
    {
        $user = $this->createUser();

        $this->authenticated()
            ->get('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'surname',
                    'description',
                    'image',
                    'organization_id',
                    'updated_at',
                    'created_at',
            ]]);
    }

    public function test_auth_user_can_get_user_by_id(): void
    {
        $user = $this->createUser();

        $this->authenticated()
            ->get('/api/v1/users/'.$user->id)
            ->assertJson([
                'name' => 'User name',
                'surname' => 'User surname',
                'description' => 'User description',
                'email' => 'test@test.com',
            ])
            ->assertStatus(200);
    }

    public function test_auth_admin_can_create_user_only_required_fields(): void
    {
        $data = [
            'name' => 'User name', 
            'email' => 'test@test.com',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/users', $data)
            ->assertCreated(201)
            ->assertJson([
                'message' => 'Invitation sent successfully',
                'user' => [
                    'name' => 'User name', 
                    'email' => 'test@test.com',
                ],
        ]);
    }

    public function test_auth_user_cannot_mass_invite_users(): void
    {
        $emails = ['testinvite1@test.com', 'testinvite2@test.com'];

        $this->authenticated()
            ->post('/api/v1/users/mass-invite', ['emails' => $emails])
            ->assertStatus(403);
    }

    public function test_auth_admin_can_mass_invite_users(): void
    {
        $emails = ['testinvite1@test.com', 'testinvite2@test.com'];

        $this->userRoleAdmin()
            ->authenticated()
            ->post('/api/v1/users/mass-invite', ['emails' => $emails])
            ->assertCreated(201)
            ->assertJson([
                'message' => 'Invitations sent successfully',
        ]);
    }

    public function test_auth_superadmin_can_mass_invite_users(): void
    {
        $emails = ['testinvite1@test.com', 'testinvite2@test.com'];

        $this->userRoleSuperAdmin()
            ->authenticated()
            ->post('/api/v1/users/mass-invite', ['emails' => $emails])
            ->assertCreated(201)
            ->assertJson([
                'message' => 'Invitations sent successfully',
        ]);
    }

    public function test_auth_user_can_update_own_account_only_required_fields(): void
    {
        $data = [
            'name' => 'Updated user name', 
            'email' => 'updated@test.com',
        ];

        $this->authenticated()
            ->put('/api/v1/users/'.$this->user->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated user name', 
                'email' => 'updated@test.com',
            ]);
    }

    public function test_auth_user_can_update_own_account_basic_data(): void
    {
        $data = [
            'name' => 'Updated user name', 
            'surname' => 'Updated user surname',
            'password' => 'newpassword',
        ];

        $this->authenticated()
            ->put('/api/v1/users/'.$this->user->id.'/basic', $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated user name', 
                'surname' => 'Updated user surname',
            ]);
    }

    public function test_auth_user_cannot_update_other_users(): void
    {
        $user = $this->createUser();

        $data = [
            'name' => 'Updated user name', 
            'email' => 'update@test.com',
        ];

        $this->authenticated()
            ->put('/api/v1/users/'.$user->id, $data)
            ->assertStatus(403);
    }

    public function test_admin_cannot_update_other_users(): void
    {
        $user = $this->createUser();

        $data = [
            'name' => 'Updated user name', 
            'email' => 'update@test.com',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put('/api/v1/users/'.$user->id, $data)
            ->assertStatus(403);
    }

    public function test_superadmin_can_update_any_user(): void
    {
        $user = $this->createUser();

        $data = [
            'name' => 'Updated user name', 
            'email' => 'updated@test.com',
        ];
        
        $this->userRoleSuperAdmin()
            ->authenticated()
            ->put('/api/v1/users/'.$user->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated user name', 
                'email' => 'updated@test.com',
            ]);
    }

    public function test_auth_user_cannot_delete_users(): void
    {
        $user = $this->createUser();

        $this->authenticated()
            ->delete('/api/v1/users/'.$user->id)
            ->assertStatus(403);

        $this->authenticated()
            ->delete('/api/v1/users/'.$this->user->id)
            ->assertStatus(403);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = $this->createUser();

        $this->userRoleAdmin()
            ->authenticated()
            ->delete('/api/v1/users/'.$user->id)
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'User deactivated successfully']
            );
    }

    public function test_admin_can_restore_user(): void
    {
        $user = $this->createUser();
        $user->delete();

        $this->userRoleAdmin()
            ->authenticated()
            ->put('/api/v1/users/'.$user->id.'/restore')
            ->assertStatus(200)
            ->assertJsonFragment([
                'deleted_at' => null,
            ]);
    }

}
