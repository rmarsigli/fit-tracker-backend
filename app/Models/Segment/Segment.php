<?php

declare(strict_types=1);

namespace App\Models\Segment;

use App\Enums\Segment\SegmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read array{type: string, coordinates: array<array<float>>}|null $route
 */
class Segment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'creator_id',
        'name',
        'description',
        'type',
        'distance_meters',
        'avg_grade_percent',
        'max_grade_percent',
        'elevation_gain',
        'total_attempts',
        'unique_athletes',
        'city',
        'state',
        'is_hazardous',
    ];

    protected function casts(): array
    {
        return [
            'type' => SegmentType::class,
            'distance_meters' => 'float',
            'avg_grade_percent' => 'float',
            'max_grade_percent' => 'float',
            'elevation_gain' => 'float',
            'is_hazardous' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function efforts(): HasMany
    {
        return $this->hasMany(SegmentEffort::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'type', 'distance_meters', 'elevation_gain', 'is_hazardous'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
