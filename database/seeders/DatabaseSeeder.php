<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(10)->create();

        $testUser = User::factory()->premium()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $allUsers = $users->push($testUser);

        $segments = Segment::factory()->count(20)->create([
            'creator_id' => $allUsers->random()->id,
        ]);

        foreach ($allUsers as $user) {
            $activities = Activity::factory()->count(rand(5, 15))->create([
                'user_id' => $user->id,
            ]);

            foreach ($activities as $activity) {
                if (rand(1, 100) > 30) {
                    SegmentEffort::factory()->create([
                        'segment_id' => $segments->random()->id,
                        'activity_id' => $activity->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }
    }
}
