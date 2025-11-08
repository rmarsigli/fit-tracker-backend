<?php declare(strict_types=1);

namespace App\Data\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Activity\Activity;
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
        public ActivityType $type,

        #[Required, Min(3)]
        public string $title,

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

        #[Nullable, Enum(ActivityVisibility::class)]
        public ActivityVisibility|Optional|null $visibility,

        #[Required]
        public string $started_at,

        #[Nullable, AfterOrEqual('started_at')]
        public ?string $completed_at,

        public string|Optional $created_at,
        public string|Optional $updated_at,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        return new self(
            id: $activity->id,
            type: $activity->type,
            title: $activity->title,
            description: $activity->description,
            distance_meters: $activity->distance_meters,
            duration_seconds: $activity->duration_seconds,
            moving_time_seconds: $activity->moving_time_seconds,
            elevation_gain: $activity->elevation_gain,
            elevation_loss: $activity->elevation_loss,
            avg_speed_kmh: $activity->avg_speed_kmh,
            max_speed_kmh: $activity->max_speed_kmh,
            avg_heart_rate: $activity->avg_heart_rate,
            max_heart_rate: $activity->max_heart_rate,
            calories: $activity->calories,
            avg_cadence: $activity->avg_cadence,
            splits: $activity->splits,
            weather: $activity->weather,
            raw_data: $activity->raw_data,
            visibility: $activity->visibility ?? Optional::create(),
            started_at: $activity->started_at?->toISOString() ?? '',
            completed_at: $activity->completed_at?->toISOString(),
            created_at: $activity->created_at?->toISOString() ?? Optional::create(),
            updated_at: $activity->updated_at?->toISOString() ?? Optional::create(),
        );
    }
}
