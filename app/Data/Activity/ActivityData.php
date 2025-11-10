<?php

declare(strict_types=1);

namespace App\Data\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Activity\Activity;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ActivityData extends Data
{
    public function __construct(
        public int|Optional $id,

        #[Required, Enum(ActivityType::class)]
        public ActivityType|Optional $type,

        #[Required, Min(3)]
        public string|Optional $title,

        #[Nullable]
        public ?string $description,

        #[Nullable, Numeric, Min(0)]
        public ?float $distance_meters,

        #[Nullable, IntegerType, Min(0)]
        public ?int $duration_seconds,

        #[Nullable, IntegerType, Min(0)]
        public ?int $moving_time_seconds,

        #[Nullable, Numeric]
        public ?float $elevation_gain,

        #[Nullable, Numeric]
        public ?float $elevation_loss,

        #[Nullable, Numeric, Min(0)]
        public ?float $avg_speed_kmh,

        #[Nullable, Numeric, Min(0)]
        public ?float $max_speed_kmh,

        #[Nullable, IntegerType, Min(30)]
        public ?int $avg_heart_rate,

        #[Nullable, IntegerType, Min(30)]
        public ?int $max_heart_rate,

        #[Nullable, IntegerType, Min(0)]
        public ?int $calories,

        #[Nullable, IntegerType, Min(0)]
        public ?int $avg_cadence,

        #[Nullable]
        public ?array $splits,

        #[Nullable]
        public ?array $weather,

        #[Nullable]
        public ?array $raw_data,

        #[Required]
        public string|Optional $started_at,

        public string|Optional $created_at,
        public string|Optional $updated_at,

        #[Nullable, AfterOrEqual('started_at')]
        public ?string $completed_at = null,

        #[Nullable, Enum(ActivityVisibility::class)]
        public ActivityVisibility|Optional|null $visibility = null,

        #[Computed]
        public float|Optional|null $distance_km = null,

        #[Computed]
        public string|Optional|null $duration_formatted = null,

        #[Computed]
        public string|Optional|null $avg_pace_min_km = null,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        $distanceMeters = $activity->distance_meters;
        $durationSeconds = $activity->duration_seconds;
        $avgSpeedKmh = $activity->avg_speed_kmh;

        $distanceKm = $distanceMeters ? round($distanceMeters / 1000, 2) : Optional::create();
        $durationFormatted = $durationSeconds ? self::staticFormatDuration($durationSeconds) : Optional::create();
        $avgPaceMinKm = ($avgSpeedKmh && $avgSpeedKmh > 0) ? self::staticCalculatePace($avgSpeedKmh) : Optional::create();

        $type = $activity->type instanceof ActivityType ? $activity->type : ActivityType::from($activity->type);

        return new self(
            id: $activity->id,
            type: $type,
            title: $activity->title,
            description: $activity->description,
            distance_meters: $distanceMeters,
            duration_seconds: $durationSeconds,
            moving_time_seconds: $activity->moving_time_seconds,
            elevation_gain: $activity->elevation_gain,
            elevation_loss: $activity->elevation_loss,
            avg_speed_kmh: $avgSpeedKmh,
            max_speed_kmh: $activity->max_speed_kmh,
            avg_heart_rate: $activity->avg_heart_rate,
            max_heart_rate: $activity->max_heart_rate,
            calories: $activity->calories,
            avg_cadence: $activity->avg_cadence,
            splits: is_string($activity->splits) ? json_decode($activity->splits, true) : $activity->splits,
            weather: is_string($activity->weather) ? json_decode($activity->weather, true) : $activity->weather,
            raw_data: is_string($activity->raw_data) ? json_decode($activity->raw_data, true) : $activity->raw_data,
            started_at: $activity->started_at instanceof \Carbon\Carbon ? $activity->started_at->toISOString() : (string) $activity->started_at,
            created_at: $activity->created_at instanceof \Carbon\Carbon ? $activity->created_at->toISOString() : Optional::create(),
            updated_at: $activity->updated_at instanceof \Carbon\Carbon ? $activity->updated_at->toISOString() : Optional::create(),
            completed_at: $activity->completed_at instanceof \Carbon\Carbon ? $activity->completed_at->toISOString() : null,
            visibility: $activity->visibility ?? Optional::create(),
            distance_km: $distanceKm,
            duration_formatted: $durationFormatted,
            avg_pace_min_km: $avgPaceMinKm,
        );
    }

    public static function getRawDataSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'points' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'lat' => ['type' => 'number'],
                            'lng' => ['type' => 'number'],
                            'alt' => ['type' => ['number', 'null']],
                            'hr' => ['type' => ['integer', 'null']],
                            'timestamp' => ['type' => 'string'],
                        ],
                        'required' => ['lat', 'lng', 'timestamp'],
                    ],
                ],
            ],
            'required' => ['points'],
        ];
    }

    public static function getWeatherSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'temp' => ['type' => ['number', 'null']],
                'feels_like' => ['type' => ['number', 'null']],
                'humidity' => ['type' => ['integer', 'null']],
                'wind_speed' => ['type' => ['number', 'null']],
                'condition' => ['type' => ['string', 'null']],
                'description' => ['type' => ['string', 'null']],
            ],
        ];
    }

    private static function staticFormatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $secs);
        }

        return sprintf('%ds', $secs);
    }

    private static function staticCalculatePace(float $speedKmh): string
    {
        if ($speedKmh <= 0) {
            return '0:00';
        }

        $paceMinutesPerKm = 60 / $speedKmh;
        $minutes = floor($paceMinutesPerKm);
        $seconds = round(($paceMinutesPerKm - $minutes) * 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
