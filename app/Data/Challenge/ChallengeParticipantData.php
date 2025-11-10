<?php

declare(strict_types=1);

namespace App\Data\Challenge;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ChallengeParticipantData extends Data
{
    public function __construct(
        public ?int $id,
        #[Required, Numeric]
        public int $challenge_id,
        #[Required, Numeric]
        public int $user_id,
        public float $current_progress = 0,
        public ?Carbon $joined_at = null,
        public ?Carbon $completed_at = null,
        public ?float $progress_percentage = null,
        public ?Carbon $created_at = null,
        public ?Carbon $updated_at = null,
    ) {}
}
