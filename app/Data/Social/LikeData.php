<?php declare(strict_types=1);

namespace App\Data\Social;

use App\Models\Social\Like;
use Spatie\LaravelData\Data;

class LikeData extends Data
{
    public function __construct(
        public int $id,
        public int $activity_id,
        public int $user_id,
        public string $created_at,
    ) {}

    public static function fromModel(Like $like): self
    {
        return new self(
            id: $like->id,
            activity_id: $like->activity_id,
            user_id: $like->user_id,
            created_at: $like->created_at->toISOString(),
        );
    }
}
