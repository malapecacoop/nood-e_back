<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, Authentication;

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        User::create([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'password',
            'invite_accepted_at' => now(),
        ]);

        $this->post('/api/v1/auth/login', [
                'email' => 'test@test.com',
                'password' => 'password',
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::create([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'password',
            'invite_accepted_at' => now(),
        ]);

        $response = $this->post('/api/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => '123456678',
        ]);

        $response->assertStatus(401);
    }

    public function test_users_can_logout(): void
    {
        $this->authenticated()
            ->post('/api/v1/logout')
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_users_can_refresh_token(): void
    {
        $this->authenticated()
            ->post('/api/v1/auth/refresh')
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
    }

    public function test_users_cannot_refresh_token_with_invalid_token(): void
    {
        $this->authenticated('invalidtoken')
            ->post('/api/v1/auth/refresh')
            ->assertStatus(401);
    }

}
