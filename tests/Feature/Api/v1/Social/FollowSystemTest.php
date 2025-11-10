<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Social\Follow;
use App\Models\User;
use App\Services\Social\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('can follow another user', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/users/{$this->otherUser->id}/follow");

    $response->assertCreated()
        ->assertJson([
            'message' => 'Successfully followed user',
        ]);

    expect(Follow::where('follower_id', $this->user->id)
        ->where('following_id', $this->otherUser->id)
        ->exists())->toBeTrue();
});

it('cannot follow yourself', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/users/{$this->user->id}/follow");

    $response->assertStatus(400)
        ->assertJson(['message' => 'You cannot follow yourself']);
});

it('cannot follow the same user twice', function () {
    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/users/{$this->otherUser->id}/follow");

    $response->assertStatus(409);
});

it('can unfollow a user', function () {
    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/users/{$this->otherUser->id}/unfollow");

    $response->assertSuccessful();

    expect(Follow::where('follower_id', $this->user->id)
        ->where('following_id', $this->otherUser->id)
        ->exists())->toBeFalse();
});

it('returns 404 when unfollowing a user not followed', function () {
    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/users/{$this->otherUser->id}/unfollow");

    $response->assertNotFound();
});

it('returns list of followers', function () {
    $followers = User::factory()->count(3)->create();

    foreach ($followers as $follower) {
        Follow::factory()->create([
            'follower_id' => $follower->id,
            'following_id' => $this->user->id,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/users/{$this->user->id}/followers");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('returns list of following', function () {
    $following = User::factory()->count(3)->create();

    foreach ($following as $followed) {
        Follow::factory()->create([
            'follower_id' => $this->user->id,
            'following_id' => $followed->id,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/users/{$this->user->id}/following");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('model method isFollowing works correctly', function () {
    expect($this->user->isFollowing($this->otherUser))->toBeFalse();

    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    expect($this->user->fresh()->isFollowing($this->otherUser))->toBeTrue();
});

it('model method isFollowedBy works correctly', function () {
    expect($this->user->isFollowedBy($this->otherUser))->toBeFalse();

    Follow::factory()->create([
        'follower_id' => $this->otherUser->id,
        'following_id' => $this->user->id,
    ]);

    expect($this->user->fresh()->isFollowedBy($this->otherUser))->toBeTrue();
});

it('followersCount method returns correct count', function () {
    $followers = User::factory()->count(5)->create();

    foreach ($followers as $follower) {
        Follow::factory()->create([
            'follower_id' => $follower->id,
            'following_id' => $this->user->id,
        ]);
    }

    expect($this->user->fresh()->followersCount())->toBe(5);
});

it('followingCount method returns correct count', function () {
    $following = User::factory()->count(4)->create();

    foreach ($following as $followed) {
        Follow::factory()->create([
            'follower_id' => $this->user->id,
            'following_id' => $followed->id,
        ]);
    }

    expect($this->user->fresh()->followingCount())->toBe(4);
});

it('feed service returns activities from followed users', function () {
    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    Activity::factory()->count(3)->create([
        'user_id' => $this->otherUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $feedService = app(FeedService::class);
    $feed = $feedService->getFollowingFeed($this->user);

    expect($feed)->toHaveCount(3);
});

it('feed service does not return private activities', function () {
    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    Activity::factory()->create([
        'user_id' => $this->otherUser->id,
        'visibility' => 'private',
        'completed_at' => now(),
    ]);

    Activity::factory()->create([
        'user_id' => $this->otherUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $feedService = app(FeedService::class);
    $feed = $feedService->getFollowingFeed($this->user);

    expect($feed)->toHaveCount(1);
});

it('feed service respects limit parameter', function () {
    Follow::factory()->create([
        'follower_id' => $this->user->id,
        'following_id' => $this->otherUser->id,
    ]);

    Activity::factory()->count(25)->create([
        'user_id' => $this->otherUser->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);

    $feedService = app(FeedService::class);
    $feed = $feedService->getFollowingFeed($this->user, 10);

    expect($feed)->toHaveCount(10);
});
