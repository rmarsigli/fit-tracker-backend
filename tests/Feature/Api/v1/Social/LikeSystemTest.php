<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Social\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->activity = Activity::factory()->create([
        'user_id' => User::factory()->create()->id,
        'visibility' => 'public',
        'completed_at' => now(),
    ]);
});

it('can like an activity', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/likes");

    $response->assertCreated()
        ->assertJson([
            'message' => 'Activity liked successfully',
            'liked' => true,
            'likes_count' => 1,
        ]);

    expect(Like::where('activity_id', $this->activity->id)
        ->where('user_id', $this->user->id)
        ->exists())->toBeTrue();
});

it('can unlike an activity', function () {
    Like::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/likes");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Like removed successfully',
            'liked' => false,
            'likes_count' => 0,
        ]);

    expect(Like::where('activity_id', $this->activity->id)
        ->where('user_id', $this->user->id)
        ->exists())->toBeFalse();
});

it('cannot like the same activity twice', function () {
    $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/likes");

    expect(Like::where('activity_id', $this->activity->id)
        ->where('user_id', $this->user->id)
        ->count())->toBe(1);

    $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/likes");

    expect(Like::where('activity_id', $this->activity->id)
        ->where('user_id', $this->user->id)
        ->count())->toBe(0);
});

it('can get list of likes for an activity', function () {
    $users = User::factory()->count(5)->create();

    foreach ($users as $user) {
        Like::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $user->id,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/activities/{$this->activity->id}/likes");

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('likes count increases correctly', function () {
    expect($this->activity->likesCount())->toBe(0);

    Like::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $this->user->id,
    ]);

    expect($this->activity->fresh()->likesCount())->toBe(1);
});

it('isLikedBy method works correctly', function () {
    expect($this->activity->isLikedBy($this->user))->toBeFalse();

    Like::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $this->user->id,
    ]);

    expect($this->activity->fresh()->isLikedBy($this->user))->toBeTrue();
});

it('deleting activity cascades likes', function () {
    Like::factory()->count(3)->create([
        'activity_id' => $this->activity->id,
    ]);

    expect(Like::where('activity_id', $this->activity->id)->count())->toBe(3);

    $this->activity->forceDelete();

    expect(Like::where('activity_id', $this->activity->id)->count())->toBe(0);
});

it('requires authentication to like activity', function () {
    $response = $this->postJson("/api/v1/activities/{$this->activity->id}/likes");

    $response->assertUnauthorized();
});

it('requires authentication to get activity likes', function () {
    $response = $this->getJson("/api/v1/activities/{$this->activity->id}/likes");

    $response->assertUnauthorized();
});
