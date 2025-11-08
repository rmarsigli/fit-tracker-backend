<?php declare(strict_types=1);

namespace Database\Factories\Segment;

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentEffortFactory extends Factory
{
    protected $model = SegmentEffort::class;

    public function definition(): array
    {
        $durationSeconds = fake()->numberBetween(60, 1800);
        $distanceMeters = 5000;
        $avgSpeedKmh = ($distanceMeters / $durationSeconds) * 3.6;

        return [
            'segment_id' => Segment::factory(),
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'duration_seconds' => $durationSeconds,
            'avg_speed_kmh' => $avgSpeedKmh,
            'avg_heart_rate' => fake()->optional()->numberBetween(130, 180),
            'rank_overall' => null,
            'rank_age_group' => null,
            'is_kom' => false,
            'is_pr' => false,
            'achieved_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function kom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_kom' => true,
            'rank_overall' => 1,
        ]);
    }

    public function pr(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pr' => true,
        ]);
    }
}
