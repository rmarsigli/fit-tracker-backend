<?php

declare(strict_types=1);

namespace App\Data\Challenge;

use App\Enums\Challenge\ChallengeType;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\Before;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ChallengeData extends Data
{
    public function __construct(
        public ?int $id,
        public ?int $created_by,
        #[Required, StringType, Max(255)]
        public string $name,
        #[StringType]
        public ?string $description,
        #[Required, Enum(ChallengeType::class)]
        public ChallengeType $type,
        #[Required, Numeric, Min(0)]
        public float $goal_value,
        #[Required, StringType, In(['km', 'hours', 'meters'])]
        public string $goal_unit,
        #[Required, Before('ends_at')]
        public Carbon $starts_at,
        #[Required, After('starts_at')]
        public Carbon $ends_at,
        public bool $is_public = true,
        #[Numeric, Min(1)]
        public ?int $max_participants = null,
        public ?int $participants_count = null,
        public ?Carbon $created_at = null,
        public ?Carbon $updated_at = null,
    ) {}
}
