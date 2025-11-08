<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'cover_photo' => $this->cover_photo,
            'bio' => $this->bio,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'weight_kg' => $this->weight_kg,
            'height_cm' => $this->height_cm,
            'city' => $this->city,
            'state' => $this->state,
            'preferences' => $this->preferences,
            'stats' => $this->stats,
            'is_premium' => $this->premium_until && $this->premium_until->isFuture(),
            'premium_until' => $this->premium_until?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
