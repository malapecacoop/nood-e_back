<?php

namespace App\Models;

use App\ImageTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, ImageTrait;

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_available',
    ];

    protected $image_fields = ['image'];

    protected $image_prefixes = [
        'image' => 'room-'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): bool
    {
        if ($this->status === true) {
            return 'available';
        }
        return 'unavailable';
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function scopeIsAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
