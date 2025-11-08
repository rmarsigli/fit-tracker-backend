<?php

declare(strict_types=1);

use App\Jobs\ProcessSegmentEfforts;
use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use App\Services\PostGIS\PostGISService;
use App\Services\Segment\SegmentMatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->matcher = app(SegmentMatcherService::class);
    $this->postGIS = app(PostGISService::class);

    $this->user = User::factory()->create();
});

it('detects and creates segment efforts for matching activity', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now(),
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $efforts = $this->matcher->processActivity($activity);

    expect($efforts)->toHaveCount(1)
        ->and($efforts->first())->toBeInstanceOf(SegmentEffort::class)
        ->and($efforts->first()->segment_id)->toBe($segment->id)
        ->and($efforts->first()->activity_id)->toBe($activity->id)
        ->and($efforts->first()->user_id)->toBe($this->user->id);
});

it('does not create effort for activity without route', function () {
    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => null,
        'completed_at' => now(),
    ]);

    $efforts = $this->matcher->processActivity($activity);

    expect($efforts)->toBeEmpty();
});

it('does not create effort for incomplete activity', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'completed_at' => null,
    ]);

    $efforts = $this->matcher->processActivity($activity);

    expect($efforts)->toBeEmpty();
});

it('marks first effort as personal record', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now(),
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $efforts = $this->matcher->processActivity($activity);

    expect($efforts->first()->is_pr)->toBeTrue();
});

it('marks faster effort as new personal record', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    // First activity (slower - 600 seconds)
    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now()->subDay(),
    ]);

    $this->matcher->processActivity($activity1);

    // Second activity (faster - 500 seconds)
    $activity2 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity2);

    $effort1 = SegmentEffort::where('activity_id', $activity1->id)->first();
    $effort2 = SegmentEffort::where('activity_id', $activity2->id)->first();

    expect($effort1->is_pr)->toBeFalse()
        ->and($effort2->is_pr)->toBeTrue();
});

it('does not mark slower effort as personal record', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    // First activity (faster - 500 seconds)
    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now()->subDay(),
    ]);

    $this->matcher->processActivity($activity1);

    // Second activity (slower - 600 seconds)
    $activity2 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity2);

    $effort2 = SegmentEffort::where('activity_id', $activity2->id)->first();

    expect($effort2->is_pr)->toBeFalse();
});

it('marks fastest overall effort as KOM', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $user2 = User::factory()->create();

    // User 1 - slower (600 seconds)
    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now()->subDay(),
    ]);

    $this->matcher->processActivity($activity1);

    // User 2 - faster (500 seconds) - should be KOM
    $activity2 = Activity::factory()->create([
        'user_id' => $user2->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity2);

    $effort1 = SegmentEffort::where('activity_id', $activity1->id)->first();
    $effort2 = SegmentEffort::where('activity_id', $activity2->id)->first();

    expect($effort1->is_kom)->toBeFalse()
        ->and($effort2->is_kom)->toBeTrue();
});

it('transfers KOM to new faster effort', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // User 1 - 600s
    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 600,
        'completed_at' => now()->subDays(2),
    ]);

    $this->matcher->processActivity($activity1);

    // User 2 - 500s (becomes KOM)
    $activity2 = Activity::factory()->create([
        'user_id' => $user2->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now()->subDay(),
    ]);

    $this->matcher->processActivity($activity2);

    // User 3 - 450s (new KOM)
    $activity3 = Activity::factory()->create([
        'user_id' => $user3->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 450,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity3);

    $effort1 = SegmentEffort::where('activity_id', $activity1->id)->first();
    $effort2 = SegmentEffort::where('activity_id', $activity2->id)->first();
    $effort3 = SegmentEffort::where('activity_id', $activity3->id)->first();

    expect($effort1->is_kom)->toBeFalse()
        ->and($effort2->fresh()->is_kom)->toBeFalse()
        ->and($effort3->is_kom)->toBeTrue();
});

it('calculates overall rank correctly', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $durations = [500, 450, 550];
    $users = [$this->user, $user2, $user3];

    foreach ($users as $index => $user) {
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'route' => $route,
            'distance_meters' => 2500,
            'duration_seconds' => $durations[$index],
            'completed_at' => now()->subDays(3 - $index),
        ]);

        $this->matcher->processActivity($activity);
    }

    $efforts = SegmentEffort::where('segment_id', $segment->id)->get();
    $rankedEfforts = $efforts->sortBy('rank_overall')->values();

    expect($rankedEfforts[0]->rank_overall)->toBe(1) // 450s
        ->and($rankedEfforts[1]->rank_overall)->toBe(2) // 500s
        ->and($rankedEfforts[2]->rank_overall)->toBe(3); // 550s
});

it('returns leaderboard for segment', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'route' => $route,
            'distance_meters' => 2500,
            'duration_seconds' => 500 + ($i * 10),
            'completed_at' => now(),
        ]);

        $this->matcher->processActivity($activity);
    }

    $leaderboard = $this->matcher->getLeaderboard($segment);

    expect($leaderboard)->toHaveCount(5)
        ->and($leaderboard->first()->duration_seconds)->toBe(500)
        ->and($leaderboard->last()->duration_seconds)->toBe(540);
});

it('returns user personal records', function () {
    $route1 = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $route2 = $this->postGIS->makeLineString([
        ['latitude' => -23.5600, 'longitude' => -46.6430],
        ['latitude' => -23.5620, 'longitude' => -46.6450],
    ]);

    $segment1 = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route1,
        'distance_meters' => 2500,
    ]);

    $segment2 = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route2,
        'distance_meters' => 3000,
    ]);

    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route1,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $activity2 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route2,
        'distance_meters' => 3000,
        'duration_seconds' => 600,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity1);
    $this->matcher->processActivity($activity2);

    $personalRecords = $this->matcher->getUserPersonalRecords($this->user->id);

    expect($personalRecords)->toHaveCount(2)
        ->and($personalRecords->every(fn ($effort) => $effort->is_pr))->toBeTrue();
});

it('returns user KOM achievements', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity);

    $komAchievements = $this->matcher->getUserKomQomAchievements($this->user->id);

    expect($komAchievements)->toHaveCount(1)
        ->and($komAchievements->first()->is_kom)->toBeTrue();
});

it('updates segment total attempts counter', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'total_attempts' => 0,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity);

    expect($segment->fresh()->total_attempts)->toBe(1);
});

it('updates segment unique athletes counter', function () {
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'unique_athletes' => 0,
    ]);

    $user2 = User::factory()->create();

    $activity1 = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    $activity2 = Activity::factory()->create([
        'user_id' => $user2->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 550,
        'completed_at' => now(),
    ]);

    $this->matcher->processActivity($activity1);
    $this->matcher->processActivity($activity2);

    expect($segment->fresh()->unique_athletes)->toBe(2);
});

it('processes segment efforts job successfully', function () {
    Queue::fake();

    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'route' => $route,
        'distance_meters' => 2500,
        'duration_seconds' => 500,
        'completed_at' => now(),
    ]);

    ProcessSegmentEfforts::dispatch($activity);

    Queue::assertPushed(ProcessSegmentEfforts::class, function ($job) use ($activity) {
        return $job->activity->id === $activity->id;
    });
});
