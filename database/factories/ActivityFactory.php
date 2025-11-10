<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(ActivityType::cases()),
            'title' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'distance_meters' => fake()->randomFloat(2, 100, 50000),
            'duration_seconds' => fake()->numberBetween(300, 10800),
            'moving_time_seconds' => fake()->numberBetween(280, 10000),
            'elevation_gain' => fake()->randomFloat(2, 0, 500),
            'elevation_loss' => fake()->randomFloat(2, 0, 500),
            'avg_speed_kmh' => fake()->randomFloat(2, 5, 40),
            'max_speed_kmh' => fake()->randomFloat(2, 10, 60),
            'avg_heart_rate' => fake()->numberBetween(100, 180),
            'max_heart_rate' => fake()->numberBetween(150, 200),
            'calories' => fake()->numberBetween(200, 1500),
            'avg_cadence' => fake()->numberBetween(60, 100),
            'visibility' => ActivityVisibility::Public,
            'started_at' => now(),
            'completed_at' => fake()->dateTimeBetween('now', '+3 hours'),
        ];
    }
}
