<?php

declare(strict_types=1);

namespace App\Models\Segment;

use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentEffort extends Model
{
    use HasFactory;

    protected $fillable = [
        'segment_id',
        'activity_id',
        'user_id',
        'duration_seconds',
        'avg_speed_kmh',
        'avg_heart_rate',
        'rank_overall',
        'rank_age_group',
        'is_kom',
        'is_pr',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'avg_speed_kmh' => 'float',
            'is_kom' => 'boolean',
            'is_pr' => 'boolean',
            'achieved_at' => 'datetime',
        ];
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
