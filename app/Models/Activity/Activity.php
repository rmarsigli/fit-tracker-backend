<?php

declare(strict_types=1);

namespace App\Models\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'distance_meters',
        'duration_seconds',
        'moving_time_seconds',
        'elevation_gain',
        'elevation_loss',
        'avg_speed_kmh',
        'max_speed_kmh',
        'avg_heart_rate',
        'max_heart_rate',
        'calories',
        'avg_cadence',
        'splits',
        'weather',
        'raw_data',
        'visibility',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'visibility' => ActivityVisibility::class,
            'distance_meters' => 'float',
            'elevation_gain' => 'float',
            'elevation_loss' => 'float',
            'avg_speed_kmh' => 'float',
            'max_speed_kmh' => 'float',
            'splits' => 'json',
            'weather' => 'json',
            'raw_data' => 'json',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segmentEfforts(): HasMany
    {
        return $this->hasMany(SegmentEffort::class);
    }
}
