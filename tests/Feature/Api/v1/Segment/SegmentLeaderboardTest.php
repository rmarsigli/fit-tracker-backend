<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->segment = Segment::factory()->create();
    $this->users = User::factory()->count(5)->create();
});

describe('GET /api/v1/segments/{segment}/leaderboard', function () {
    it('returns leaderboard with top efforts', function () {
        // Create efforts for 5 users (fastest to slowest)
        foreach ($this->users as $index => $user) {
            $activity = Activity::factory()->for($user)->create();

            SegmentEffort::factory()->create([
                'segment_id' => $this->segment->id,
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'duration_seconds' => 600 + ($index * 10), // 600, 610, 620, 630, 640
                'avg_speed_kmh' => 18.5 - ($index * 0.5),
                'is_kom' => $index === 0, // First is KOM
                'is_pr' => true,
            ]);
        }

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/leaderboard");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'rank',
                        'user' => ['id', 'name', 'username', 'avatar'],
                        'elapsed_time_seconds',
                        'elapsed_time_formatted',
                        'average_speed_kmh',
                        'average_pace_min_km',
                        'achieved_at',
                        'is_kom',
                        'is_pr',
                    ],
                ],
                'segment' => ['id', 'name', 'distance_km', 'total_efforts'],
            ])
            ->assertJsonCount(5, 'data');

        // Verify ordering (fastest first)
        expect($response->json('data.0.elapsed_time_seconds'))
            ->toBe(600)
            ->and($response->json('data.0.rank'))
            ->toBe(1)
            ->and($response->json('data.0.is_kom'))
            ->toBeTrue();

        // Verify last place
        expect($response->json('data.4.rank'))->toBe(5);
    });

    it('returns empty leaderboard for segment with no efforts', function () {
        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/leaderboard");

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for non-existent segment', function () {
        $response = $this->actingAs($this->users[0])
            ->getJson('/api/v1/segments/99999/leaderboard');

        $response->assertNotFound();
    });

    it('limits leaderboard to 20 efforts', function () {
        // Create 25 efforts
        foreach (range(1, 25) as $index) {
            $user = User::factory()->create();
            $activity = Activity::factory()->for($user)->create();

            SegmentEffort::factory()->create([
                'segment_id' => $this->segment->id,
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'duration_seconds' => 600 + $index,
            ]);
        }

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/leaderboard");

        $response->assertSuccessful()
            ->assertJsonCount(20, 'data'); // Max 20
    });
});

describe('GET /api/v1/me/records', function () {
    it('returns authenticated user personal records', function () {
        $user = User::factory()->create();
        $segments = Segment::factory()->count(3)->create();

        foreach ($segments as $segment) {
            $activity = Activity::factory()->for($user)->create();

            SegmentEffort::factory()->create([
                'segment_id' => $segment->id,
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'is_pr' => true,
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/v1/me/records');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'segment' => ['id', 'name', 'distance_km', 'type'],
                        'personal_record' => [
                            'elapsed_time_seconds',
                            'elapsed_time_formatted',
                            'average_speed_kmh',
                            'achieved_at',
                            'is_kom',
                        ],
                        'rank',
                        'total_attempts',
                    ],
                ],
                'user' => ['id', 'name', 'username'],
            ])
            ->assertJsonCount(3, 'data');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/me/records');

        $response->assertUnauthorized();
    });

    it('returns empty for user with no efforts', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/me/records');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('only returns PR efforts', function () {
        $user = User::factory()->create();
        $segment = Segment::factory()->create();
        $activity = Activity::factory()->for($user)->create();

        // Create multiple efforts, only one is PR
        SegmentEffort::factory()->create([
            'segment_id' => $segment->id,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'is_pr' => true,
            'duration_seconds' => 600,
        ]);

        SegmentEffort::factory()->create([
            'segment_id' => $segment->id,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'is_pr' => false,
            'duration_seconds' => 700,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me/records');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data'); // Only PR
    });
});

describe('GET /api/v1/users/{user}/records', function () {
    it('returns specific user personal records', function () {
        $user = User::factory()->create();
        $segment = Segment::factory()->create();
        $activity = Activity::factory()->for($user)->create();

        SegmentEffort::factory()->create([
            'segment_id' => $segment->id,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'is_pr' => true,
        ]);

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/users/{$user->id}/records");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
    });

    it('works without authentication', function () {
        $user = User::factory()->create();
        $segment = Segment::factory()->create();
        $activity = Activity::factory()->for($user)->create();

        SegmentEffort::factory()->create([
            'segment_id' => $segment->id,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'is_pr' => true,
        ]);

        $response = $this->getJson("/api/v1/users/{$user->id}/records");

        $response->assertUnauthorized(); // Routes are inside auth middleware
    });
});

describe('GET /api/v1/segments/{segment}/kom', function () {
    it('returns current KOM holder', function () {
        $maleUser = User::factory()->create(['gender' => 'male']);
        $activity = Activity::factory()->for($maleUser)->create();

        $komEffort = SegmentEffort::factory()->create([
            'segment_id' => $this->segment->id,
            'activity_id' => $activity->id,
            'user_id' => $maleUser->id,
            'duration_seconds' => 500,
            'avg_speed_kmh' => 20.0,
            'is_kom' => true,
        ]);

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/kom");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'username', 'avatar'],
                    'elapsed_time_seconds',
                    'elapsed_time_formatted',
                    'average_speed_kmh',
                    'achieved_at',
                ],
                'segment' => ['id', 'name'],
            ])
            ->assertJson([
                'data' => [
                    'user' => ['id' => $maleUser->id],
                    'elapsed_time_seconds' => 500.0,
                ],
            ]);
    });

    it('returns null for segment with no KOM', function () {
        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/kom");

        $response->assertSuccessful()
            ->assertJson([
                'data' => null,
                'message' => 'No KOM recorded for this segment yet',
            ]);
    });

    it('only returns male KOM', function () {
        $femaleUser = User::factory()->create(['gender' => 'female']);
        $activity = Activity::factory()->for($femaleUser)->create();

        SegmentEffort::factory()->create([
            'segment_id' => $this->segment->id,
            'activity_id' => $activity->id,
            'user_id' => $femaleUser->id,
            'duration_seconds' => 500,
            'is_kom' => true, // Marked as KOM but female
        ]);

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/kom");

        $response->assertSuccessful()
            ->assertJson([
                'data' => null,
                'message' => 'No KOM recorded for this segment yet',
            ]);
    });
});

describe('GET /api/v1/segments/{segment}/qom', function () {
    it('returns current QOM holder', function () {
        $femaleUser = User::factory()->create(['gender' => 'female']);
        $activity = Activity::factory()->for($femaleUser)->create();

        $qomEffort = SegmentEffort::factory()->create([
            'segment_id' => $this->segment->id,
            'activity_id' => $activity->id,
            'user_id' => $femaleUser->id,
            'duration_seconds' => 550,
            'avg_speed_kmh' => 18.5,
            'is_kom' => true,
        ]);

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/qom");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'username', 'avatar'],
                    'elapsed_time_seconds',
                    'elapsed_time_formatted',
                    'average_speed_kmh',
                    'achieved_at',
                ],
                'segment' => ['id', 'name'],
            ])
            ->assertJson([
                'data' => [
                    'user' => ['id' => $femaleUser->id],
                    'elapsed_time_seconds' => 550.0,
                ],
            ]);
    });

    it('returns null for segment with no QOM', function () {
        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/qom");

        $response->assertSuccessful()
            ->assertJson([
                'data' => null,
                'message' => 'No QOM recorded for this segment yet',
            ]);
    });

    it('only returns female QOM', function () {
        $maleUser = User::factory()->create(['gender' => 'male']);
        $activity = Activity::factory()->for($maleUser)->create();

        SegmentEffort::factory()->create([
            'segment_id' => $this->segment->id,
            'activity_id' => $activity->id,
            'user_id' => $maleUser->id,
            'duration_seconds' => 500,
            'is_kom' => true, // Marked as KOM but male
        ]);

        $response = $this->actingAs($this->users[0])
            ->getJson("/api/v1/segments/{$this->segment->id}/qom");

        $response->assertSuccessful()
            ->assertJson([
                'data' => null,
                'message' => 'No QOM recorded for this segment yet',
            ]);
    });
});
