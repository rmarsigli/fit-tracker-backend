<?php declare(strict_types=1);

use App\Enums\Activity\ActivityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('validates required fields on start', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/start', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title']);
});

it('validates type enum on start', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/start', [
            'type' => 'invalid_type',
            'title' => 'Test',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('validates GPS coordinates on track', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/fake_id/track', [
            'latitude' => 91,
            'longitude' => 181,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('validates latitude range', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/fake_id/track', [
            'latitude' => -91,
            'longitude' => 0,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

it('validates longitude range', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/fake_id/track', [
            'latitude' => 0,
            'longitude' => -181,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);
});

it('validates heart rate range on track', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/tracking/fake_id/track', [
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'heart_rate' => 400,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['heart_rate']);
});

it('requires authentication for start endpoint', function () {
    $response = $this->postJson('/api/v1/tracking/start', [
        'type' => ActivityType::Run->value,
        'title' => 'Test',
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for track endpoint', function () {
    $response = $this->postJson('/api/v1/tracking/fake_id/track', [
        'latitude' => -23.5505,
        'longitude' => -46.6333,
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for pause endpoint', function () {
    $response = $this->postJson('/api/v1/tracking/fake_id/pause');

    $response->assertUnauthorized();
});

it('requires authentication for resume endpoint', function () {
    $response = $this->postJson('/api/v1/tracking/fake_id/resume');

    $response->assertUnauthorized();
});

it('requires authentication for finish endpoint', function () {
    $response = $this->postJson('/api/v1/tracking/fake_id/finish');

    $response->assertUnauthorized();
});

it('requires authentication for status endpoint', function () {
    $response = $this->getJson('/api/v1/tracking/fake_id/status');

    $response->assertUnauthorized();
});
