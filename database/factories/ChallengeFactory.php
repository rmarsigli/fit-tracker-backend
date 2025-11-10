<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Challenge\ChallengeType;
use App\Models\Challenge\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChallengeFactory extends Factory
{
    protected $model = Challenge::class;

    public function definition(): array
    {
        $type = fake()->randomElement(ChallengeType::cases());
        $startsAt = fake()->dateTimeBetween('-1 week', '+1 week');
        $endsAt = fake()->dateTimeBetween($startsAt, '+2 weeks');

        return [
            'created_by' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => $type,
            'goal_value' => match ($type) {
                ChallengeType::Distance => fake()->randomFloat(2, 10, 500),
                ChallengeType::Duration => fake()->randomFloat(2, 5, 100),
                ChallengeType::Elevation => fake()->randomFloat(2, 100, 5000),
            },
            'goal_unit' => $type->unit(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_public' => fake()->boolean(80),
            'max_participants' => fake()->boolean(30) ? fake()->numberBetween(10, 100) : null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
        ]);
    }

    public function upcoming(): self
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(9),
        ]);
    }

    public function ended(): self
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(3),
        ]);
    }

    public function distanceChallenge(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChallengeType::Distance,
            'goal_value' => fake()->randomFloat(2, 50, 300),
            'goal_unit' => 'km',
        ]);
    }

    public function durationChallenge(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChallengeType::Duration,
            'goal_value' => fake()->randomFloat(2, 10, 50),
            'goal_unit' => 'hours',
        ]);
    }

    public function elevationChallenge(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChallengeType::Elevation,
            'goal_value' => fake()->randomFloat(2, 500, 3000),
            'goal_unit' => 'meters',
        ]);
    }
}
