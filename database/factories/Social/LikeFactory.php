<?php

declare(strict_types=1);

namespace Database\Factories\Social;

use App\Models\Activity\Activity;
use App\Models\Social\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Social\Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
        ];
    }
}
