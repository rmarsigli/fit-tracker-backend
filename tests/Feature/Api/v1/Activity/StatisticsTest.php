<?php declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can get user statistics', function () {
    Activity::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'completed_at' => now(),
        'distance_meters' => 5000,
        'duration_seconds' => 1800,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/me');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'total_activities',
                'total_distance_meters',
                'total_distance_km',
                'total_duration_seconds',
                'total_duration_hours',
                'avg_distance_per_activity',
                'avg_duration_per_activity',
                'by_type',
                'last_7_days',
                'last_30_days',
            ],
        ]);

    $data = $response->json('data');
    expect($data['total_activities'])->toBe(5);
    expect($data['total_distance_km'])->toBe(25);
});

it('returns empty stats for user without activities', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/me');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data['total_activities'])->toBe(0);
    expect($data['total_distance_km'])->toBe(0);
});

it('can get activity feed', function () {
    Activity::factory()->count(10)->create([
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    Activity::factory()->count(5)->create([
        'visibility' => 'private',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/feed');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'title'],
            ],
            'meta' => ['count'],
        ]);

    expect($response->json('meta.count'))->toBe(10);
});

it('limits feed results correctly', function () {
    Activity::factory()->count(50)->create([
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/feed?limit=15');

    $response->assertSuccessful();

    expect($response->json('meta.count'))->toBe(15);
});

it('enforces maximum limit on feed', function () {
    Activity::factory()->count(150)->create([
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/feed?limit=200');

    $response->assertSuccessful();

    expect($response->json('meta.count'))->toBeLessThanOrEqual(100);
});

it('can get activity splits', function () {
    $rawData = [
        'points' => [
            ['lat' => -23.5505, 'lng' => -46.6333, 'alt' => 760, 'hr' => 140, 'timestamp' => now()->subMinutes(10)->toISOString()],
            ['lat' => -23.5515, 'lng' => -46.6343, 'alt' => 765, 'hr' => 145, 'timestamp' => now()->subMinutes(5)->toISOString()],
            ['lat' => -23.5525, 'lng' => -46.6353, 'alt' => 770, 'hr' => 150, 'timestamp' => now()->toISOString()],
        ],
    ];

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'raw_data' => $rawData,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/statistics/activities/{$activity->id}/splits");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'activity_id',
                'splits',
            ],
        ]);
});

it('returns empty splits for activity without GPS data', function () {
    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'raw_data' => null,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/statistics/activities/{$activity->id}/splits");

    $response->assertSuccessful();

    expect($response->json('data.splits'))->toBe([]);
});

it('can get activity pace zones', function () {
    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'avg_speed_kmh' => 10,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/statistics/activities/{$activity->id}/pace-zones");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'activity_id',
                'pace_zones' => [
                    'recovery',
                    'easy',
                    'moderate',
                    'tempo',
                    'threshold',
                    'interval',
                ],
            ],
        ]);

    $zones = $response->json('data.pace_zones');
    expect($zones['recovery'])->toHaveKeys(['min_pace', 'max_pace', 'description', 'min_pace_formatted', 'max_pace_formatted']);
});

it('returns empty pace zones for activity without speed data', function () {
    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'avg_speed_kmh' => null,
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/statistics/activities/{$activity->id}/pace-zones");

    $response->assertSuccessful();

    expect($response->json('data.pace_zones'))->toBe([]);
});

it('requires authentication for all statistics endpoints', function () {
    $activity = Activity::factory()->create();

    $this->getJson('/api/v1/statistics/me')->assertUnauthorized();
    $this->getJson('/api/v1/statistics/feed')->assertUnauthorized();
    $this->getJson("/api/v1/statistics/activities/{$activity->id}/splits")->assertUnauthorized();
    $this->getJson("/api/v1/statistics/activities/{$activity->id}/pace-zones")->assertUnauthorized();
});

it('calculates stats grouped by activity type', function () {
    Activity::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'type' => 'run',
        'completed_at' => now(),
        'distance_meters' => 5000,
    ]);

    Activity::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'type' => 'ride',
        'completed_at' => now(),
        'distance_meters' => 10000,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/me');

    $response->assertSuccessful();

    $byType = $response->json('data.by_type');
    expect($byType)->toHaveKeys(['run', 'ride']);
    expect($byType['run']['count'])->toBe(3);
    expect($byType['ride']['count'])->toBe(2);
});

it('filters activities by date range for last 7 days', function () {
    Activity::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'completed_at' => now()->subDays(3),
    ]);

    Activity::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'completed_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/me');

    $response->assertSuccessful();

    expect($response->json('data.last_7_days.count'))->toBe(5);
    expect($response->json('data.total_activities'))->toBe(8);
});

it('filters activities by date range for last 30 days', function () {
    Activity::factory()->count(7)->create([
        'user_id' => $this->user->id,
        'completed_at' => now()->subDays(15),
    ]);

    Activity::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'completed_at' => now()->subDays(45),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/statistics/me');

    $response->assertSuccessful();

    expect($response->json('data.last_30_days.count'))->toBe(7);
    expect($response->json('data.total_activities'))->toBe(10);
});
