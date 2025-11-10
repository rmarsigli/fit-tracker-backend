<?php

declare(strict_types=1);

namespace App\Data\Segment;

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
        public string $name,

        #[Nullable]
        public ?string $description,

        #[Nullable, Enum(SegmentType::class)]
        public SegmentType|Optional|null $type,

        #[Nullable, Numeric, Between(100, 100000)]
        public float|Optional|null $distance_meters,

        #[Nullable, Numeric]
        public ?float $avg_grade_percent,

        #[Nullable, Numeric]
        public ?float $max_grade_percent,

        #[Nullable, Numeric]
        public ?float $elevation_gain,

        #[Nullable, IntegerType]
        public int|Optional|null $total_attempts,

        #[Nullable, IntegerType]
        public int|Optional|null $unique_athletes,

        #[Nullable]
        public ?string $city,

        #[Nullable]
        public ?string $state,

        #[Nullable, BooleanType]
        public bool|Optional|null $is_hazardous,

        public string|Optional $created_at,
        public string|Optional $updated_at,

        #[Computed]
        public float|Optional|null $distance_km = null,
    ) {}

    public static function fromModel(Segment $segment): self
    {
        $distanceMeters = $segment->distance_meters ?? Optional::create();
        $distanceKm = is_float($distanceMeters) || is_int($distanceMeters)
            ? round($distanceMeters / 1000, 2)
            : Optional::create();

        return new self(
            id: $segment->id,
            name: $segment->name,
            description: $segment->description,
            type: $segment->type ?? Optional::create(),
            distance_meters: $distanceMeters,
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
            distance_km: $distanceKm,
        );
    }
}
