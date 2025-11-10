<?php

declare(strict_types=1);

use App\Enums\Challenge\ChallengeType;
use App\Models\Activity\Activity;
use App\Models\Challenge\Challenge;
use App\Models\Challenge\ChallengeParticipant;
use App\Models\User;
use App\Services\Challenge\ChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

it('lists public challenges', function () {
    Challenge::factory()->count(5)->create(['is_public' => true]);
    Challenge::factory()->count(2)->create(['is_public' => false]);

    $response = $this->getJson('/api/v1/challenges');

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('creates a new challenge', function () {
    $challengeData = [
        'name' => 'January Distance Challenge',
        'description' => 'Run 100km in January',
        'type' => ChallengeType::Distance->value,
        'goal_value' => 100.00,
        'goal_unit' => 'km',
        'starts_at' => now()->addDay()->toIso8601String(),
        'ends_at' => now()->addDays(31)->toIso8601String(),
        'is_public' => true,
        'max_participants' => 50,
    ];

    $response = $this->postJson('/api/v1/challenges', $challengeData);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'January Distance Challenge');

    expect($response->json('data.goal_value'))->toEqual(100.00);

    $this->assertDatabaseHas('challenges', [
        'name' => 'January Distance Challenge',
        'created_by' => $this->user->id,
    ]);
});

it('shows a challenge', function () {
    $challenge = Challenge::factory()->create(['is_public' => true]);

    $response = $this->getJson("/api/v1/challenges/{$challenge->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $challenge->id)
        ->assertJsonPath('data.name', $challenge->name);
});

it('updates a challenge', function () {
    $challenge = Challenge::factory()->create(['created_by' => $this->user->id]);

    $response = $this->putJson("/api/v1/challenges/{$challenge->id}", [
        'name' => 'Updated Challenge Name',
        'description' => $challenge->description,
        'type' => $challenge->type->value,
        'goal_value' => 200.00,
        'goal_unit' => $challenge->goal_unit,
        'starts_at' => $challenge->starts_at->toIso8601String(),
        'ends_at' => $challenge->ends_at->toIso8601String(),
        'is_public' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Challenge Name');

    expect($response->json('data.goal_value'))->toEqual(200.00);
});

it('prevents unauthorized user from updating challenge', function () {
    $otherUser = User::factory()->create();
    $challenge = Challenge::factory()->create(['created_by' => $otherUser->id]);

    $response = $this->putJson("/api/v1/challenges/{$challenge->id}", [
        'name' => 'Hacked Challenge',
        'description' => $challenge->description,
        'type' => $challenge->type->value,
        'goal_value' => 200.00,
        'goal_unit' => $challenge->goal_unit,
        'starts_at' => $challenge->starts_at->toIso8601String(),
        'ends_at' => $challenge->ends_at->toIso8601String(),
        'is_public' => true,
    ]);

    $response->assertForbidden();
});

it('deletes a challenge', function () {
    $challenge = Challenge::factory()->create(['created_by' => $this->user->id]);

    $response = $this->deleteJson("/api/v1/challenges/{$challenge->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('challenges', ['id' => $challenge->id]);
});

it('prevents unauthorized user from deleting challenge', function () {
    $otherUser = User::factory()->create();
    $challenge = Challenge::factory()->create(['created_by' => $otherUser->id]);

    $response = $this->deleteJson("/api/v1/challenges/{$challenge->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('challenges', ['id' => $challenge->id]);
});

it('allows user to join a challenge', function () {
    $challenge = Challenge::factory()->active()->create();

    $response = $this->postJson("/api/v1/challenges/{$challenge->id}/join");

    $response->assertOk()
        ->assertJsonPath('message', 'Successfully joined the challenge');

    $this->assertDatabaseHas('challenge_participants', [
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
    ]);
});

it('prevents user from joining full challenge', function () {
    $challenge = Challenge::factory()->active()->create(['max_participants' => 2]);
    ChallengeParticipant::factory()->count(2)->create(['challenge_id' => $challenge->id]);

    $response = $this->postJson("/api/v1/challenges/{$challenge->id}/join");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Challenge is full');
});

it('prevents user from joining ended challenge', function () {
    $challenge = Challenge::factory()->ended()->create();

    $response = $this->postJson("/api/v1/challenges/{$challenge->id}/join");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Challenge has already ended');
});

it('prevents user from joining challenge twice', function () {
    $challenge = Challenge::factory()->active()->create();
    ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/challenges/{$challenge->id}/join");

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'User is already participating in this challenge');
});

it('allows user to leave a challenge', function () {
    $challenge = Challenge::factory()->active()->create();
    ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
        'completed_at' => null,
    ]);

    $response = $this->deleteJson("/api/v1/challenges/{$challenge->id}/leave");

    $response->assertOk()
        ->assertJsonPath('message', 'Successfully left the challenge');

    $this->assertDatabaseMissing('challenge_participants', [
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
    ]);
});

it('shows challenge leaderboard', function () {
    $challenge = Challenge::factory()->active()->distanceChallenge()->create();
    $participants = ChallengeParticipant::factory()->count(5)->create([
        'challenge_id' => $challenge->id,
    ]);

    $response = $this->getJson("/api/v1/challenges/{$challenge->id}/leaderboard");

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('lists user challenges', function () {
    $challenge1 = Challenge::factory()->active()->create();
    $challenge2 = Challenge::factory()->active()->create();
    $challenge3 = Challenge::factory()->active()->create();

    ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge1->id,
        'user_id' => $this->user->id,
    ]);
    ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge2->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/v1/challenges/my');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters user challenges by status', function () {
    $activeChallenge = Challenge::factory()->active()->create();
    $upcomingChallenge = Challenge::factory()->upcoming()->create();

    ChallengeParticipant::factory()->create([
        'challenge_id' => $activeChallenge->id,
        'user_id' => $this->user->id,
    ]);
    ChallengeParticipant::factory()->create([
        'challenge_id' => $upcomingChallenge->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/v1/challenges/my?status=active');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeChallenge->id);
});

it('lists available challenges', function () {
    $challenge1 = Challenge::factory()->active()->create(['is_public' => true]);
    $challenge2 = Challenge::factory()->active()->create(['is_public' => true]);
    $challenge3 = Challenge::factory()->active()->create(['is_public' => true]);

    ChallengeParticipant::create([
        'challenge_id' => $challenge1->id,
        'user_id' => $this->user->id,
        'current_progress' => 0,
        'joined_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/challenges/available');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('updates progress when activity is created', function () {
    $challenge = Challenge::factory()->active()->distanceChallenge()->create(['goal_value' => 100]);
    $participant = ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
        'current_progress' => 50,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'distance_meters' => 25000,
        'started_at' => now(),
    ]);

    $service = app(ChallengeService::class);
    $service->updateProgress($participant, $activity);

    $participant->refresh();

    expect((float) $participant->current_progress)->toBe(75.0);
});

it('marks challenge as completed when goal is reached', function () {
    $challenge = Challenge::factory()->active()->distanceChallenge()->create(['goal_value' => 100]);
    $participant = ChallengeParticipant::factory()->create([
        'challenge_id' => $challenge->id,
        'user_id' => $this->user->id,
        'current_progress' => 95,
        'completed_at' => null,
    ]);

    $activity = Activity::factory()->create([
        'user_id' => $this->user->id,
        'distance_meters' => 10000,
        'started_at' => now(),
    ]);

    $service = app(ChallengeService::class);
    $service->updateProgress($participant, $activity);

    $participant->refresh();

    expect((float) $participant->current_progress)->toBe(105.0)
        ->and($participant->completed_at)->not->toBeNull();
});
