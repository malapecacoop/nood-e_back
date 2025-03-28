<?php

namespace App\Models;

use App\ImageTrait;
use App\Notifications\UserInviteNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, ImageTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'description',
        'password',
        'reset_password',
        'permissions',
        'role_id',
        'organization_id',
        'role_id',
        'image',
        'invite_accepted_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $primaryKey = 'id';

    protected $image_fields = ['image'];

    protected $image_prefixes = [
        'image' => 'user-'
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'invite_accepted_at' => 'datetime',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function eventsAuthored(): HasMany
    {
        return $this->hasMany(Event::class, 'author_id');
    }

    public function eventsMember(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user', 'user_id', 'event_id');
    }

    public function discussionsAuthored(): HasMany
    {
        return $this->hasMany(Discussion::class, 'author_id');
    }

    public function discussionsMember(): BelongsToMany
    {
        return $this->belongsToMany(Discussion::class, 'discussion_user', 'user_id', 'discussion_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function sendInviteNotification()
    {
        $frontUrl = $this->getInviteUrl();

        $this->notify(new UserInviteNotification($frontUrl));
    }

    public function getInviteUrl(): string
    {
        $expirationInSeconds = null;
        // 7 days
        // $expirationInSeconds = 60 * 60 * 24 * 7;

        $url = URL::signedRoute('invitation', $this, $expirationInSeconds, false);

        $userId = $this->id;
        $signature = substr($url, strpos($url, '?signature=') + 11);

        return config('app.frontend_url') . '/invitation?user_id=' . $userId . '&signature=' . $signature;
    }

    public function isAdmin(): bool
    {
        return $this->role_id === Role::ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role_id === Role::SUPERADMIN;
    }
}
