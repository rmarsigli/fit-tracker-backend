<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => fake()->imageUrl(200, 200, 'people'),
            'cover_photo' => fake()->imageUrl(1200, 400, 'nature'),
            'bio' => fake()->optional()->sentence(),
            'birth_date' => fake()->optional()->date('Y-m-d', '-18 years'),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other']),
            'weight_kg' => fake()->optional()->randomFloat(2, 50, 120),
            'height_cm' => fake()->optional()->randomFloat(2, 150, 210),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'preferences' => [
                'units' => fake()->randomElement(['metric', 'imperial']),
                'privacy' => fake()->randomElement(['public', 'followers', 'private']),
            ],
            'stats' => [
                'total_activities' => 0,
                'total_distance' => 0,
                'total_duration' => 0,
            ],
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'premium_until' => now()->addYear(),
        ]);
    }
}
