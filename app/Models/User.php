<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method static create(array $array)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'cover_photo',
        'bio',
        'birth_date',
        'gender',
        'weight_kg',
        'height_cm',
        'city',
        'state',
        'preferences',
        'stats',
        'strava_id',
        'premium_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'preferences' => 'json',
            'stats' => 'json',
            'premium_until' => 'datetime',
        ];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity\Activity::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(Segment\Segment::class, 'creator_id');
    }

    public function segmentEfforts(): HasMany
    {
        return $this->hasMany(Segment\SegmentEffort::class);
    }
}
