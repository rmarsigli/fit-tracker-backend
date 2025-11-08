<?php

declare(strict_types=1);

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Enums\Segment\SegmentType;
use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user with all attributes', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeNull()
        ->and($user->username)->not->toBeNull()
        ->and($user->email)->not->toBeNull();
});

it('can create an activity with enum types', function () {
    $activity = Activity::factory()->create([
        'type' => ActivityType::Run,
        'visibility' => ActivityVisibility::Public,
    ]);

    expect($activity->type)->toBe(ActivityType::Run)
        ->and($activity->visibility)->toBe(ActivityVisibility::Public);
});

it('user has many activities relationship', function () {
    $user = User::factory()->create();
    Activity::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->activities)->toHaveCount(3);
});

it('user has many segments relationship', function () {
    $user = User::factory()->create();
    Segment::factory()->count(2)->create(['creator_id' => $user->id]);

    expect($user->segments)->toHaveCount(2);
});

it('activity belongs to user relationship', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $user->id]);

    expect($activity->user->id)->toBe($user->id);
});

it('segment belongs to creator relationship', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['creator_id' => $user->id]);

    expect($segment->creator->id)->toBe($user->id);
});

it('segment effort has all relationships', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $user->id]);
    $segment = Segment::factory()->create();

    $effort = SegmentEffort::factory()->create([
        'segment_id' => $segment->id,
        'activity_id' => $activity->id,
        'user_id' => $user->id,
    ]);

    expect($effort->segment->id)->toBe($segment->id)
        ->and($effort->activity->id)->toBe($activity->id)
        ->and($effort->user->id)->toBe($user->id);
});

it('can create segment with enum type', function () {
    $segment = Segment::factory()->create([
        'type' => SegmentType::Ride,
    ]);

    expect($segment->type)->toBe(SegmentType::Ride);
});

it('soft deletes users', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    expect(User::find($userId))->toBeNull()
        ->and(User::withTrashed()->find($userId))->not->toBeNull();
});

it('soft deletes activities', function () {
    $activity = Activity::factory()->create();
    $activityId = $activity->id;

    $activity->delete();

    expect(Activity::find($activityId))->toBeNull()
        ->and(Activity::withTrashed()->find($activityId))->not->toBeNull();
});

it('casts json fields correctly', function () {
    $user = User::factory()->create([
        'preferences' => ['units' => 'metric'],
        'stats' => ['total_activities' => 10],
    ]);

    expect($user->preferences)->toBeArray()
        ->and($user->preferences['units'])->toBe('metric')
        ->and($user->stats)->toBeArray()
        ->and($user->stats['total_activities'])->toBe(10);
});

it('casts datetime fields correctly', function () {
    $activity = Activity::factory()->create();

    expect($activity->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($activity->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('activity has segment efforts relationship', function () {
    $activity = Activity::factory()->create();
    SegmentEffort::factory()->count(2)->create(['activity_id' => $activity->id]);

    expect($activity->segmentEfforts)->toHaveCount(2);
});

it('segment has efforts relationship', function () {
    $segment = Segment::factory()->create();
    SegmentEffort::factory()->count(3)->create(['segment_id' => $segment->id]);

    expect($segment->efforts)->toHaveCount(3);
});
