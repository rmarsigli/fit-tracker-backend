<?php declare(strict_types=1);

namespace App\Data\Activity;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TrackLocationData extends Data
{
    public function __construct(
        #[Required, Numeric, Between(-90, 90)]
        public float $latitude,

        #[Required, Numeric, Between(-180, 180)]
        public float $longitude,

        #[Nullable, Numeric]
        public ?float $altitude,

        #[Nullable, IntegerType, Between(30, 220)]
        public ?int $heart_rate,
    ) {}
}
