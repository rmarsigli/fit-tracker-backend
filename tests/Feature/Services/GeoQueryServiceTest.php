<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\User;
use App\Services\PostGIS\GeoQueryService;
use App\Services\PostGIS\PostGISService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(GeoQueryService::class);
    $this->postGIS = app(PostGISService::class);
});

it('finds activities near a point', function () {
    $user = User::factory()->create();

    // Create activity at São Paulo (-23.5505, -46.6333)
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route,
    ]);

    // Search near the activity location
    $activities = $this->service->findActivitiesNearPoint(
        -23.5505,
        -46.6333,
        5000.0 // 5km radius
    );

    expect($activities)->toHaveCount(1);
});

it('does not find activities outside radius', function () {
    $user = User::factory()->create();

    // Create activity at São Paulo (-23.5505, -46.6333)
    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route,
    ]);

    // Search 10km away
    $activities = $this->service->findActivitiesNearPoint(
        -23.6505, // ~11km away
        -46.6333,
        5000.0 // 5km radius
    );

    expect($activities)->toHaveCount(0);
});

it('finds segments near a point', function () {
    $user = User::factory()->create();

    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => $route,
    ]);

    $segments = $this->service->findSegmentsNearPoint(
        -23.5505,
        -46.6333,
        5000.0
    );

    expect($segments)->toHaveCount(1);
});

it('finds users near a point', function () {
    $location = $this->postGIS->makePoint(-23.5505, -46.6333);

    User::factory()->create([
        'location' => $location,
    ]);

    $users = $this->service->findUsersNearPoint(
        -23.5505,
        -46.6333,
        10000.0 // 10km
    );

    expect($users)->toHaveCount(1);
});

it('finds intersecting segments for an activity', function () {
    $user = User::factory()->create();

    // Create activity route
    $activityRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $activityRoute,
    ]);

    // Create fully overlapping segment (same route)
    $segmentRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => $segmentRoute,
    ]);

    $intersectingSegments = $this->service->findIntersectingSegments($activity, 90.0);

    expect($intersectingSegments)->toHaveCount(1)
        ->and($intersectingSegments->first())->toHaveKey('segment')
        ->toHaveKey('overlap_percentage')
        ->toHaveKey('overlap_distance');
});

it('does not find non-intersecting segments', function () {
    $user = User::factory()->create();

    // Create activity route
    $activityRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5510, 'longitude' => -46.6340],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $activityRoute,
    ]);

    // Create non-overlapping segment
    $segmentRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5600, 'longitude' => -46.6430],
        ['latitude' => -23.5610, 'longitude' => -46.6440],
    ]);

    Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => $segmentRoute,
    ]);

    $intersectingSegments = $this->service->findIntersectingSegments($activity);

    expect($intersectingSegments)->toHaveCount(0);
});

it('finds activities intersecting a segment', function () {
    $user = User::factory()->create();

    // Create segment
    $segmentRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6335],
        ['latitude' => -23.5515, 'longitude' => -46.6345],
    ]);

    $segment = Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => $segmentRoute,
    ]);

    // Create activity that intersects
    $activityRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5500, 'longitude' => -46.6330],
        ['latitude' => -23.5520, 'longitude' => -46.6350],
    ]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $activityRoute,
    ]);

    $activities = $this->service->findActivitiesIntersectingSegment($segment);

    expect($activities)->toHaveCount(1);
});

it('calculates distance between two activities', function () {
    $user = User::factory()->create();

    $route1 = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $route2 = $this->postGIS->makeLineString([
        ['latitude' => -23.5605, 'longitude' => -46.6433],
        ['latitude' => -23.5615, 'longitude' => -46.6443],
    ]);

    $startPoint1 = $this->postGIS->makePoint(-23.5505, -46.6333);
    $startPoint2 = $this->postGIS->makePoint(-23.5605, -46.6433);

    $activity1 = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route1,
        'start_point' => $startPoint1,
    ]);

    $activity2 = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route2,
        'start_point' => $startPoint2,
    ]);

    $distance = $this->service->calculateDistanceBetweenActivities($activity1, $activity2);

    expect($distance)->toBeFloat()
        ->toBeGreaterThan(0);
});

it('returns null when calculating distance without routes', function () {
    $user = User::factory()->create();

    $activity1 = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => null,
    ]);

    $activity2 = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => null,
    ]);

    $distance = $this->service->calculateDistanceBetweenActivities($activity1, $activity2);

    expect($distance)->toBeNull();
});

it('finds activities in bounding box', function () {
    $user = User::factory()->create();

    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route,
    ]);

    $activities = $this->service->findActivitiesInBoundingBox(
        -23.5520, -46.6350, // min lat, min lng
        -23.5490, -46.6320  // max lat, max lng
    );

    expect($activities)->toHaveCount(1);
});

it('finds segments in bounding box', function () {
    $user = User::factory()->create();

    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => $route,
    ]);

    $segments = $this->service->findSegmentsInBoundingBox(
        -23.5520, -46.6350,
        -23.5490, -46.6320
    );

    expect($segments)->toHaveCount(1);
});

it('finds similar route activities', function () {
    $user = User::factory()->create();

    // Create first activity
    $route1 = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $activity1 = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route1,
    ]);

    // Create similar route (very close)
    $route2 = $this->postGIS->makeLineString([
        ['latitude' => -23.5506, 'longitude' => -46.6334],
        ['latitude' => -23.5516, 'longitude' => -46.6344],
    ]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route2,
    ]);

    $similarActivities = $this->service->findSimilarRouteActivities($activity1, 500.0);

    expect($similarActivities)->toHaveCount(1);
});

it('does not include the same activity in similar routes', function () {
    $user = User::factory()->create();

    $route = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => $route,
    ]);

    $similarActivities = $this->service->findSimilarRouteActivities($activity, 1000.0);

    expect($similarActivities)->not->toContain($activity);
});

it('respects limit parameter when finding activities near point', function () {
    $user = User::factory()->create();

    // Create 10 activities
    for ($i = 0; $i < 10; $i++) {
        $route = $this->postGIS->makeLineString([
            ['latitude' => -23.5505 + ($i * 0.0001), 'longitude' => -46.6333],
            ['latitude' => -23.5515 + ($i * 0.0001), 'longitude' => -46.6343],
        ]);

        Activity::factory()->create([
            'user_id' => $user->id,
            'route' => $route,
        ]);
    }

    $activities = $this->service->findActivitiesNearPoint(
        -23.5505,
        -46.6333,
        5000.0,
        5 // limit to 5
    );

    expect($activities)->toHaveCount(5);
});

it('returns empty collection when finding intersecting segments for activity without route', function () {
    $user = User::factory()->create();

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'route' => null,
    ]);

    $segments = $this->service->findIntersectingSegments($activity);

    expect($segments)->toBeEmpty();
});

it('returns empty collection when finding activities intersecting segment without route', function () {
    $user = User::factory()->create();

    $segment = Segment::factory()->create([
        'creator_id' => $user->id,
        'route' => null,
    ]);

    $activities = $this->service->findActivitiesIntersectingSegment($segment);

    expect($activities)->toBeEmpty();
});
