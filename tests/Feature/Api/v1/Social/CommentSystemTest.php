<?php

declare(strict_types=1);

use App\Models\Activity\Activity;
use App\Models\Social\Comment;
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

it('can create a comment on an activity', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/comments", [
            'content' => 'Great activity!',
        ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Comment created successfully',
        ]);

    expect(Comment::where('activity_id', $this->activity->id)
        ->where('user_id', $this->user->id)
        ->where('content', 'Great activity!')
        ->exists())->toBeTrue();
});

it('validates required content field', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/comments", [
            'content' => '',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

it('validates content minimum length', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/comments", [
            'content' => '',
        ]);

    $response->assertUnprocessable();
});

it('validates content maximum length', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/activities/{$this->activity->id}/comments", [
            'content' => str_repeat('a', 1001),
        ]);

    $response->assertUnprocessable();
});

it('can get list of comments for an activity', function () {
    Comment::factory()->count(5)->create([
        'activity_id' => $this->activity->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/activities/{$this->activity->id}/comments");

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('can delete own comment', function () {
    $comment = Comment::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $this->user->id,
        'content' => 'My comment',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/comments/{$comment->id}");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Comment deleted successfully',
        ]);

    expect(Comment::find($comment->id))->toBeNull();
});

it('cannot delete another user comment', function () {
    $otherUser = User::factory()->create();
    $comment = Comment::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/comments/{$comment->id}");

    $response->assertForbidden();

    expect(Comment::find($comment->id))->not->toBeNull();
});

it('comments count works correctly', function () {
    expect($this->activity->commentsCount())->toBe(0);

    Comment::factory()->count(3)->create([
        'activity_id' => $this->activity->id,
    ]);

    expect($this->activity->fresh()->commentsCount())->toBe(3);
});

it('deleting activity cascades comments', function () {
    Comment::factory()->count(3)->create([
        'activity_id' => $this->activity->id,
    ]);

    expect(Comment::where('activity_id', $this->activity->id)->count())->toBe(3);

    $this->activity->forceDelete();

    expect(Comment::where('activity_id', $this->activity->id)->count())->toBe(0);
});

it('comments are ordered by latest first', function () {
    $oldComment = Comment::factory()->create([
        'activity_id' => $this->activity->id,
        'created_at' => now()->subHours(2),
    ]);

    $newComment = Comment::factory()->create([
        'activity_id' => $this->activity->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/activities/{$this->activity->id}/comments");

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data[0]['id'])->toBe($newComment->id);
    expect($data[1]['id'])->toBe($oldComment->id);
});

it('requires authentication to create comment', function () {
    $response = $this->postJson("/api/v1/activities/{$this->activity->id}/comments", [
        'content' => 'Test',
    ]);

    $response->assertUnauthorized();
});

it('requires authentication to get comments', function () {
    $response = $this->getJson("/api/v1/activities/{$this->activity->id}/comments");

    $response->assertUnauthorized();
});

it('requires authentication to delete comment', function () {
    $comment = Comment::factory()->create([
        'activity_id' => $this->activity->id,
    ]);

    $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

    $response->assertUnauthorized();
});
