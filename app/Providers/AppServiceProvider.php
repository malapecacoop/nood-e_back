<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Organization;
use App\Models\Room;
use App\Models\Topic;
use App\Observers\CommentObserver;
use App\Observers\DiscussionObserver;
use App\Observers\OrganizationObserver;
use App\Observers\RoomObserver;
use App\Observers\TopicObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/auth/nova-contrasenya?token=$token&email={$notifiable->getEmailForPasswordReset()}";
        });
        Comment::observe(CommentObserver::class);
        Room::observe(RoomObserver::class);
        Topic::observe(TopicObserver::class);
        Discussion::observe(DiscussionObserver::class);
        Organization::observe(OrganizationObserver::class);
    }
}
