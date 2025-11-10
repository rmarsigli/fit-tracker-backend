<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Challenge\Challenge;
use App\Models\Challenge\ChallengeParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChallengeParticipantFactory extends Factory
{
    protected $model = ChallengeParticipant::class;

    public function definition(): array
    {
        $challenge = Challenge::factory()->create();

        return [
            'challenge_id' => $challenge->id,
            'user_id' => User::factory(),
            'current_progress' => fake()->randomFloat(2, 0, $challenge->goal_value * 0.8),
            'joined_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            $challenge = Challenge::find($attributes['challenge_id']);

            return [
                'current_progress' => $challenge->goal_value + fake()->randomFloat(2, 0, 50),
                'completed_at' => now()->subDays(fake()->numberBetween(0, 5)),
            ];
        });
    }

    public function inProgress(): self
    {
        return $this->state(function (array $attributes) {
            $challenge = Challenge::find($attributes['challenge_id']);

            return [
                'current_progress' => fake()->randomFloat(2, 1, $challenge->goal_value * 0.9),
                'completed_at' => null,
            ];
        });
    }
}
