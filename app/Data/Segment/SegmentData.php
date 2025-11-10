<?php

declare(strict_types=1);

namespace App\Data\Segment;

use App\Data\User\UserData;
use App\Enums\Segment\SegmentType;
use App\Models\Segment\Segment;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class SegmentData extends Data
{
    public function __construct(
        public int|Optional $id,

        #[Required, Min(3)]
        public string|Optional $name,

        #[Nullable]
        public ?string $description,

        #[Required, Enum(SegmentType::class)]
        public SegmentType|Optional $type,

        #[Required, Numeric, Between(100, 100000)]
        public float|Optional $distance_meters,

        #[Required]
        public string|Optional $route,

        #[Nullable, Numeric]
        public ?float $avg_grade_percent,

        #[Nullable, Numeric]
        public ?float $max_grade_percent,

        #[Nullable, Numeric]
        public ?float $elevation_gain,

        #[Nullable, IntegerType]
        public int|Optional|null $total_attempts = null,

        #[Nullable, IntegerType]
        public int|Optional|null $unique_athletes = null,

        public string|Optional $created_at = '',
        public string|Optional $updated_at = '',

        #[Nullable]
        public ?string $city = null,

        #[Nullable]
        public ?string $state = null,

        #[Nullable, BooleanType]
        public bool|Optional|null $is_hazardous = null,

        #[Computed]
        public UserData|Optional|null $creator = null,

        #[Computed]
        public float|Optional|null $distance_km = null,
    ) {}

    public static function fromModel(Segment $segment): self
    {
        $distanceMeters = $segment->distance_meters ?? Optional::create();
        $distanceKm = ($distanceMeters instanceof Optional)
            ? Optional::create()
            : round($distanceMeters / 1000, 2);

        $creator = $segment->relationLoaded('creator') && $segment->creator
            ? UserData::from($segment->creator)
            : Optional::create();

        return new self(
            id: $segment->id,
            name: $segment->name,
            description: $segment->description,
            type: $segment->type ?? Optional::create(),
            distance_meters: $distanceMeters,
            route: $segment->route ?? Optional::create(),
            avg_grade_percent: $segment->avg_grade_percent,
            max_grade_percent: $segment->max_grade_percent,
            elevation_gain: $segment->elevation_gain,
            total_attempts: $segment->total_attempts ?? Optional::create(),
            unique_athletes: $segment->unique_athletes ?? Optional::create(),
            city: $segment->city,
            state: $segment->state,
            is_hazardous: $segment->is_hazardous ?? Optional::create(),
            created_at: $segment->created_at?->toISOString() ?? Optional::create(),
            updated_at: $segment->updated_at?->toISOString() ?? Optional::create(),
            creator: $creator,
            distance_km: $distanceKm,
        );
    }
}
