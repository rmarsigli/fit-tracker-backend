<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static create(array $array)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

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

    public function followers(): HasMany
    {
        return $this->hasMany(Social\Follow::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(Social\Follow::class, 'follower_id');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()
            ->where('following_id', $user->id)
            ->exists();
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()
            ->where('follower_id', $user->id)
            ->exists();
    }

    public function followersCount(): int
    {
        return $this->followers()->count();
    }

    public function followingCount(): int
    {
        return $this->following()->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'bio', 'city', 'state'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
