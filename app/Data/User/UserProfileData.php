<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UserProfileData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $username,
        public string $email,
        public int|Optional $followers_count,
        public int|Optional $following_count,
        public bool|Optional $is_following,
        public string $created_at,
    ) {}

    public static function fromModel(User $user, ?User $authUser = null): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            username: $user->username,
            email: $user->email,
            followers_count: $user->followersCount(),
            following_count: $user->followingCount(),
            is_following: $authUser ? $authUser->isFollowing($user) : Optional::create(),
            created_at: $user->created_at->toISOString(),
        );
    }
}
