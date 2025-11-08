<?php declare(strict_types=1);

namespace App\Data\Segment;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class NearbySegmentsData extends Data
{
    public function __construct(
        #[Required, Numeric, Between(-90, 90)]
        public float $latitude,

        #[Required, Numeric, Between(-180, 180)]
        public float $longitude,

        #[Nullable, IntegerType, Min(1)]
        public ?int $radius = 10,
    ) {}
}
