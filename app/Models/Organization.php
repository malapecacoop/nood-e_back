<?php

namespace App\Models;

use App\ImageTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, ImageTrait;

    protected $fillable = [
        'name', 
        'description',
        'image',
        'owner_id'
    ];

    protected $image_fields = ['image'];

    protected $image_prefixes = [
        'image' => 'organization-'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
