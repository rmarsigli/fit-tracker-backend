<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Social\Follow;
use App\Models\Social\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns following feed for user with follows', function () {
    $followedUser = User::factory()->create();

    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $followedUser->id,
    ]);

    Activity::factory()->count(3)->create([
        'user_id' => $followedUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/following');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('returns empty following feed when not following anyone', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/following');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('does not show private activities in following feed', function () {
    $followedUser = User::factory()->create();

    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $followedUser->id,
    ]);

    Activity::factory()->create([
        'user_id' => $followedUser->id,
        'visibility' => 'private',
        'completed_at' => now(),
    ]);

    Activity::factory()->create([
        'user_id' => $followedUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/following');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('respects limit parameter in following feed', function () {
    $followedUser = User::factory()->create();

    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $followedUser->id,
    ]);

    Activity::factory()->count(30)->create([
        'user_id' => $followedUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/following?limit=5');

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('returns nearby feed with valid coordinates', function () {
    $linestring = DB::raw('ST_SetSRID(ST_MakeLine(ST_MakePoint(-46.6333, -23.5505), ST_MakePoint(-46.6340, -23.5510)), 4326)');

    Activity::factory()->count(3)->create([
        'visibility' => 'public',
        'completed_at' => now(),
        'route' => $linestring,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/nearby?lat=-23.5505&lng=-46.6333&radius=10');

    $response->assertSuccessful();
});

it('validates required lat and lng for nearby feed', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/nearby');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['lat', 'lng']);
});

it('validates latitude range for nearby feed', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/nearby?lat=100&lng=0');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['lat']);
});

it('validates longitude range for nearby feed', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/nearby?lat=0&lng=200');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['lng']);
});

it('returns trending feed based on likes', function () {
    $activity1 = Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(2),
    ]);

    $activity2 = Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(3),
    ]);

    Like::factory()->count(5)->create(['activity_id' => $activity1->id]);
    Like::factory()->count(2)->create(['activity_id' => $activity2->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/trending');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect($data[0]['id'])->toBe($activity1->id);
    expect($data[1]['id'])->toBe($activity2->id);
});

it('trending feed only shows activities with likes', function () {
    Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(2),
    ]);

    $activityWithLikes = Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(3),
    ]);

    Like::factory()->count(3)->create(['activity_id' => $activityWithLikes->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/trending');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('trending feed respects days parameter', function () {
    $recentActivity = Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(3),
    ]);

    $oldActivity = Activity::factory()->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(10),
    ]);

    Like::factory()->count(3)->create(['activity_id' => $recentActivity->id]);
    Like::factory()->count(5)->create(['activity_id' => $oldActivity->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/trending?days=7');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $data = $response->json('data');
    expect($data[0]['id'])->toBe($recentActivity->id);
});

it('trending feed respects limit parameter', function () {
    $activities = Activity::factory()->count(10)->create([
        'visibility' => 'public',
        'completed_at' => now()->subDays(2),
    ]);

    foreach ($activities as $activity) {
        Like::factory()->count(rand(1, 5))->create(['activity_id' => $activity->id]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/feed/trending?limit=5');

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('requires authentication for following feed', function () {
    $response = $this->getJson('/api/v1/feed/following');

    $response->assertUnauthorized();
});

it('requires authentication for nearby feed', function () {
    $response = $this->getJson('/api/v1/feed/nearby?lat=0&lng=0');

    $response->assertUnauthorized();
});

it('requires authentication for trending feed', function () {
    $response = $this->getJson('/api/v1/feed/trending');

    $response->assertUnauthorized();
});
