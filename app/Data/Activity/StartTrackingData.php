<?php declare(strict_types=1);

namespace App\Data\Activity;

use App\Enums\Activity\ActivityType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class StartTrackingData extends Data
{
    public function __construct(
        #[Required, Enum(ActivityType::class)]
        public ActivityType $type,

        #[Required, Min(3)]
        public string $title,
    ) {}
}
