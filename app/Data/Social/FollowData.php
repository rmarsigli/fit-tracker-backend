<?php

declare(strict_types=1);

namespace App\Data\Social;

use App\Models\Social\Follow;
use Spatie\LaravelData\Data;

class FollowData extends Data
{
    public function __construct(
        public int $id,
        public int $follower_id,
        public int $following_id,
        public string $created_at,
    ) {}

    public static function fromModel(Follow $follow): self
    {
        return new self(
            id: $follow->id,
            follower_id: $follow->follower_id,
            following_id: $follow->following_id,
            created_at: $follow->created_at->toISOString(),
        );
    }
}
