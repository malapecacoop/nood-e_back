<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurrency extends Model
{
    use HasFactory;

    const TYPE_DAY = 1;
    const TYPE_WEEK = 2;
    const TYPE_MONTH = 3;
    const TYPE_YEAR = 4;

    const DAYS_GENERATE = 547; // 1.5 years

    protected $fillable = [
        'type',
        'end',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
