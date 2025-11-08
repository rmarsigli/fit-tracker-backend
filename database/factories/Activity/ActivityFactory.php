<?php declare(strict_types=1);

namespace Database\Factories\Activity;

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
        $type = fake()->randomElement(ActivityType::cases());
        $distanceMeters = fake()->randomFloat(2, 1000, 50000);
        $durationSeconds = fake()->numberBetween(300, 7200);
        $movingTime = (int) ($durationSeconds * fake()->randomFloat(2, 0.85, 0.98));

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->getTitleForType($type),
            'description' => fake()->optional()->sentence(),
            'distance_meters' => $distanceMeters,
            'duration_seconds' => $durationSeconds,
            'moving_time_seconds' => $movingTime,
            'elevation_gain' => fake()->randomFloat(2, 0, 500),
            'elevation_loss' => fake()->randomFloat(2, 0, 500),
            'avg_speed_kmh' => ($distanceMeters / $movingTime) * 3.6,
            'max_speed_kmh' => fake()->randomFloat(2, 20, 60),
            'avg_heart_rate' => fake()->optional()->numberBetween(120, 170),
            'max_heart_rate' => fake()->optional()->numberBetween(170, 200),
            'calories' => fake()->optional()->numberBetween(200, 1500),
            'avg_cadence' => fake()->optional()->numberBetween(70, 95),
            'visibility' => fake()->randomElement(ActivityVisibility::cases()),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'completed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    private function getTitleForType(ActivityType $type): string
    {
        return match ($type) {
            ActivityType::Run => fake()->randomElement(['Morning Run', 'Evening Run', 'Long Run', 'Speed Workout']),
            ActivityType::Ride => fake()->randomElement(['Morning Ride', 'Evening Ride', 'Long Ride', 'Hill Climb']),
            ActivityType::Walk => fake()->randomElement(['Morning Walk', 'Evening Walk', 'Casual Walk']),
            ActivityType::Swim => fake()->randomElement(['Pool Swim', 'Open Water Swim', 'Swim Workout']),
            ActivityType::Gym => fake()->randomElement(['Strength Training', 'Cardio Workout', 'CrossFit']),
            ActivityType::Other => fake()->randomElement(['Workout', 'Exercise', 'Training']),
        };
    }
}
