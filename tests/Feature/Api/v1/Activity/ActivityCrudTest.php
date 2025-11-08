<?php

declare(strict_types=1);

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can list user activities', function () {
    Activity::factory()->count(5)->create(['user_id' => $this->user->id]);
    Activity::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/activities');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'title', 'distance_km', 'duration_seconds'],
            ],
            'meta' => ['total'],
        ])
        ->assertJsonCount(5, 'data');
});

it('can create an activity', function () {
    $activityData = [
        'type' => ActivityType::Run->value,
        'title' => 'Morning Run',
        'description' => 'A nice morning run',
        'visibility' => ActivityVisibility::Public->value,
        'started_at' => now()->subHour()->toISOString(),
        'completed_at' => now()->toISOString(),
        'distance_meters' => 5000,
        'duration_seconds' => 1800,
        'moving_time_seconds' => 1750,
        'elevation_gain' => 50,
        'avg_speed_kmh' => 10,
        'avg_heart_rate' => 150,
        'calories' => 300,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/activities', $activityData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'type', 'title', 'distance_km', 'duration_seconds'],
        ]);

    $this->assertDatabaseHas('activities', [
        'user_id' => $this->user->id,
        'type' => ActivityType::Run->value,
        'title' => 'Morning Run',
        'distance_meters' => 5000,
    ]);
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/activities', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title', 'started_at']);
});

it('validates activity type enum', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/activities', [
            'type' => 'invalid_type',
            'title' => 'Test',
            'started_at' => now()->toISOString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('validates completed_at is after started_at', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/activities', [
            'type' => ActivityType::Run->value,
            'title' => 'Test',
            'started_at' => now()->toISOString(),
            'completed_at' => now()->subHour()->toISOString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['completed_at']);
});

it('can show an activity', function () {
    $activity = Activity::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/activities/{$activity->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['id', 'type', 'title', 'distance_km'],
        ])
        ->assertJson([
            'data' => ['id' => $activity->id],
        ]);
});

it('cannot show another user activity', function () {
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/activities/{$activity->id}");

    $response->assertForbidden();
});

it('can update own activity', function () {
    $activity = Activity::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/activities/{$activity->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Atividade atualizada com sucesso',
        ]);

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'title' => 'Updated Title',
        'description' => 'Updated description',
    ]);
});

it('cannot update another user activity', function () {
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/activities/{$activity->id}", [
            'title' => 'Hacked Title',
        ]);

    $response->assertForbidden();
});

it('can delete own activity', function () {
    $activity = Activity::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/activities/{$activity->id}");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Atividade excluída com sucesso',
        ]);

    $this->assertSoftDeleted('activities', [
        'id' => $activity->id,
    ]);
});

it('cannot delete another user activity', function () {
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/activities/{$activity->id}");

    $response->assertForbidden();
});

it('requires authentication for all endpoints', function () {
    $activity = Activity::factory()->create();

    $this->getJson('/api/v1/activities')->assertUnauthorized();
    $this->postJson('/api/v1/activities', [])->assertUnauthorized();
    $this->getJson("/api/v1/activities/{$activity->id}")->assertUnauthorized();
    $this->putJson("/api/v1/activities/{$activity->id}", [])->assertUnauthorized();
    $this->deleteJson("/api/v1/activities/{$activity->id}")->assertUnauthorized();
});
