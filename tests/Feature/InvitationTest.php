<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'name' => 'User name',
            'surname' => 'User surname',
            'description' => 'User description',
            'email' => 'test@test.com',
        ]);
    }

    public function test_email_invitation_send(): void
    {
        Notification::fake();

        $user = $this->createUser();

        $user->sendInviteNotification();

        Notification::assertSentTo(
            $user,
            UserInviteNotification::class,
            function ($notification, $channels) use ($user) {
                $mailData = $notification->toMail($user);
                $url = $user->getInviteUrl();
                $this->assertStringContainsString($url, $mailData->actionUrl);
                return true;
            }
        );
    }

    public function test_email_invitation_accept(): void
    {
        $user = $this->createUser();

        $url = URL::signedRoute('invitation', $user, null, false);
        $url = url($url);

        $this->post($url)
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
    }

    public function test_email_invitation_accept_with_expired_signature(): void
    {
        $user = $this->createUser();

        $url = URL::signedRoute('invitation', $user, -1, false);
        $url = url($url);

        $this->post($url)
            ->assertStatus(401)
            ->assertJson(['message' => 'Invalid invitation']);
    } 
}
