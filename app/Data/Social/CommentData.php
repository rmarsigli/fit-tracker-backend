<?php declare(strict_types=1);

namespace App\Data\Social;

use App\Data\User\UserProfileData;
use App\Models\Social\Comment;
use App\Models\User;
use Spatie\LaravelData\Data;

class CommentData extends Data
{
    public function __construct(
        public int $id,
        public int $activity_id,
        public int $user_id,
        public string $content,
        public UserProfileData $user,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Comment $comment, ?User $authUser = null): self
    {
        return new self(
            id: $comment->id,
            activity_id: $comment->activity_id,
            user_id: $comment->user_id,
            content: $comment->content,
            user: UserProfileData::from($comment->user, $authUser),
            created_at: $comment->created_at->toISOString(),
            updated_at: $comment->updated_at->toISOString(),
        );
    }
}
