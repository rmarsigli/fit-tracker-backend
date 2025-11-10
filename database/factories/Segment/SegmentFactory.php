<?php

declare(strict_types=1);

namespace Database\Factories\Segment;

use App\Enums\Segment\SegmentType;
use App\Models\Segment\Segment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        $type = fake()->randomElement(SegmentType::cases());

        return [
            'creator_id' => User::factory(),
            'name' => $this->getNameForType($type),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'distance_meters' => fake()->randomFloat(2, 500, 10000),
            'avg_grade_percent' => fake()->randomFloat(2, -5, 10),
            'max_grade_percent' => fake()->randomFloat(2, 0, 20),
            'elevation_gain' => fake()->randomFloat(2, 0, 300),
            'total_attempts' => 0,
            'unique_athletes' => 0,
            'city' => fake()->city(),
            'state' => fake()->randomElement(['California', 'New York', 'Texas', 'Florida', 'Washington', 'Oregon']),
            'is_hazardous' => fake()->boolean(10),
        ];
    }

    private function getNameForType(SegmentType $type): string
    {
        return match ($type) {
            SegmentType::Run => fake()->randomElement(['Hill Climb', 'Sprint Segment', 'Long Straight', 'Park Loop']),
            SegmentType::Ride => fake()->randomElement(['Mountain Pass', 'Coastal Road', 'City Sprint', 'Valley Climb']),
        };
    }
}
