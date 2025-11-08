<?php

declare(strict_types=1);

use App\Enums\Segment\SegmentType;
use App\Models\Segment\Segment;
use App\Models\User;
use App\Services\PostGIS\PostGISService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->postGIS = app(PostGISService::class);

    $this->sampleRoute = $this->postGIS->makeLineString([
        ['latitude' => -23.5505, 'longitude' => -46.6333],
        ['latitude' => -23.5515, 'longitude' => -46.6343],
    ]);
});

it('can list all segments', function () {
    Segment::factory()->count(5)->create(['creator_id' => $this->user->id]);
    Segment::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'type', 'distance_km', 'creator'],
            ],
            'meta' => ['total'],
        ])
        ->assertJsonCount(8, 'data');
});

it('can filter segments by creator', function () {
    Segment::factory()->count(3)->create(['creator_id' => $this->user->id]);
    Segment::factory()->count(2)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments?my_segments=1');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can filter segments by type', function () {
    Segment::factory()->count(3)->create([
        'creator_id' => $this->user->id,
        'type' => SegmentType::Run,
    ]);

    Segment::factory()->count(2)->create([
        'creator_id' => $this->user->id,
        'type' => SegmentType::Ride,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments?type=run');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can create a segment', function () {
    $segmentData = [
        'name' => 'Paulista Climb',
        'description' => 'Steep climb on Paulista Avenue',
        'type' => SegmentType::Run->value,
        'distance_meters' => 1200,
        'avg_grade_percent' => 5.5,
        'max_grade_percent' => 8.2,
        'elevation_gain' => 66,
        'city' => 'São Paulo',
        'state' => 'SP',
        'is_hazardous' => false,
        'route' => $this->sampleRoute,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/segments', $segmentData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'type', 'distance_km', 'creator'],
        ]);

    $this->assertDatabaseHas('segments', [
        'creator_id' => $this->user->id,
        'name' => 'Paulista Climb',
        'type' => SegmentType::Run->value,
        'distance_meters' => 1200,
    ]);
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/segments', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type', 'distance_meters', 'route']);
});

it('validates segment type enum', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/segments', [
            'name' => 'Test Segment',
            'type' => 'invalid_type',
            'distance_meters' => 1000,
            'route' => $this->sampleRoute,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('validates minimum distance', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/segments', [
            'name' => 'Test Segment',
            'type' => SegmentType::Run->value,
            'distance_meters' => 30,
            'route' => $this->sampleRoute,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['distance_meters']);
});

it('validates maximum distance', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/segments', [
            'name' => 'Test Segment',
            'type' => SegmentType::Run->value,
            'distance_meters' => 150000,
            'route' => $this->sampleRoute,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['distance_meters']);
});

it('can show a segment', function () {
    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/segments/{$segment->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'type', 'distance_km', 'creator'],
        ])
        ->assertJson([
            'data' => [
                'id' => $segment->id,
                'name' => $segment->name,
            ],
        ]);
});

it('can update own segment', function () {
    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/segments/{$segment->id}", [
            'name' => 'Updated Segment Name',
            'description' => 'Updated description',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Segmento atualizado com sucesso',
            'data' => [
                'id' => $segment->id,
                'name' => 'Updated Segment Name',
                'description' => 'Updated description',
            ],
        ]);

    $this->assertDatabaseHas('segments', [
        'id' => $segment->id,
        'name' => 'Updated Segment Name',
    ]);
});

it('cannot update another user segment', function () {
    $otherUser = User::factory()->create();
    $segment = Segment::factory()->create([
        'creator_id' => $otherUser->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/segments/{$segment->id}", [
            'name' => 'Hacked Segment',
        ]);

    $response->assertForbidden();
});

it('can delete own segment', function () {
    $segment = Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/segments/{$segment->id}");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Segmento deletado com sucesso',
        ]);

    $this->assertDatabaseMissing('segments', [
        'id' => $segment->id,
    ]);
});

it('cannot delete another user segment', function () {
    $otherUser = User::factory()->create();
    $segment = Segment::factory()->create([
        'creator_id' => $otherUser->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/segments/{$segment->id}");

    $response->assertForbidden();
});

it('can find nearby segments', function () {
    Segment::factory()->create([
        'creator_id' => $this->user->id,
        'route' => $this->sampleRoute,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments/nearby?latitude=-23.5505&longitude=-46.6333&radius=5000');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'type', 'distance_km'],
            ],
            'meta' => ['total', 'latitude', 'longitude', 'radius_meters'],
        ]);
});

it('validates nearby required parameters', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments/nearby');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('validates latitude range for nearby', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments/nearby?latitude=100&longitude=-46.6333');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

it('validates longitude range for nearby', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/segments/nearby?latitude=-23.5505&longitude=200');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);
});

it('requires authentication for all endpoints', function () {
    $segment = Segment::factory()->create(['route' => $this->sampleRoute]);

    expect($this->getJson('/api/v1/segments'))->assertUnauthorized();
    expect($this->getJson("/api/v1/segments/{$segment->id}"))->assertUnauthorized();
    expect($this->postJson('/api/v1/segments', []))->assertUnauthorized();
    expect($this->putJson("/api/v1/segments/{$segment->id}", []))->assertUnauthorized();
    expect($this->deleteJson("/api/v1/segments/{$segment->id}"))->assertUnauthorized();
    expect($this->getJson('/api/v1/segments/nearby?latitude=-23.5505&longitude=-46.6333'))->assertUnauthorized();
});
