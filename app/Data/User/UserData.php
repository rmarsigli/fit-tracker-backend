<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $username,
        public string|Optional|null $avatar,
        public string|Optional|null $cover_photo,
        public string|Optional|null $bio,
        public string|Optional|null $city,
        public string|Optional|null $state,
        public string|Optional $created_at,
        public string|Optional $updated_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            username: $user->username,
            avatar: $user->avatar ?? Optional::create(),
            cover_photo: $user->cover_photo ?? Optional::create(),
            bio: $user->bio ?? Optional::create(),
            city: $user->city ?? Optional::create(),
            state: $user->state ?? Optional::create(),
            created_at: $user->created_at?->toISOString() ?? Optional::create(),
            updated_at: $user->updated_at?->toISOString() ?? Optional::create(),
        );
    }
}
