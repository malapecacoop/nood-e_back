<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\Authentication;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, Authentication;

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $this->post('/api/v1/auth/forgot-password',
            ['email' => $this->user->email]
        );

        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $this->post('/api/v1/auth/forgot-password', ['email' => $this->user->email]);

        Notification::assertSentTo($this->user, ResetPassword::class, function (object $notification) {
            $this->post('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $this->user->email,
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])->assertStatus(200);

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $this->post('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(302);
    }
}
