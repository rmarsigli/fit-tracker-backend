<?php

declare(strict_types=1);

use App\Http\Controllers\Api\v1\Activity\ActivityController;
use App\Http\Controllers\Api\v1\Activity\ActivityTrackingController;
use App\Http\Controllers\Api\v1\Activity\StatisticsController;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Challenge\ChallengeController;
use App\Http\Controllers\Api\v1\Segment\SegmentController;
use App\Http\Controllers\Api\v1\Segment\SegmentLeaderboardController;
use App\Http\Controllers\Api\v1\Social\CommentController;
use App\Http\Controllers\Api\v1\Social\FeedController;
use App\Http\Controllers\Api\v1\Social\FollowController;
use App\Http\Controllers\Api\v1\Social\LikeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

// Health Check Endpoints (no auth required)
Route::get('health', [HealthController::class, 'index']);
Route::get('health/ready', [HealthController::class, 'ready']);
Route::get('health/detailed', [HealthController::class, 'detailed']);

// Metrics Endpoint (no auth for now - add auth in production!)
Route::get('metrics', [MetricsController::class, 'index']);

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:5,1');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::apiResource('activities', ActivityController::class);

        Route::get('segments/nearby', [SegmentController::class, 'nearby']);
        Route::apiResource('segments', SegmentController::class);

        // Segment Leaderboards & Records
        Route::get('segments/{segment}/leaderboard', [SegmentLeaderboardController::class, 'index']);
        Route::get('segments/{segment}/kom', [SegmentLeaderboardController::class, 'kom']);
        Route::get('segments/{segment}/qom', [SegmentLeaderboardController::class, 'qom']);
        Route::get('me/records', [SegmentLeaderboardController::class, 'userRecords']);
        Route::get('users/{user}/records', [SegmentLeaderboardController::class, 'userRecords']);

        Route::prefix('tracking')->group(function () {
            Route::post('start', [ActivityTrackingController::class, 'start']);
            Route::post('{activityId}/track', [ActivityTrackingController::class, 'track']);
            Route::post('{activityId}/pause', [ActivityTrackingController::class, 'pause']);
            Route::post('{activityId}/resume', [ActivityTrackingController::class, 'resume']);
            Route::post('{activityId}/finish', [ActivityTrackingController::class, 'finish']);
            Route::get('{activityId}/status', [ActivityTrackingController::class, 'status']);
        });

        Route::prefix('statistics')->group(function () {
            Route::get('me', [StatisticsController::class, 'userStats']);
            Route::get('feed', [StatisticsController::class, 'activityFeed']);
            Route::get('activities/{activity}/splits', [StatisticsController::class, 'activitySplits']);
            Route::get('activities/{activity}/pace-zones', [StatisticsController::class, 'activityPaceZones']);
        });

        Route::post('users/{user}/follow', [FollowController::class, 'follow']);
        Route::delete('users/{user}/unfollow', [FollowController::class, 'unfollow']);
        Route::get('users/{user}/followers', [FollowController::class, 'followers']);
        Route::get('users/{user}/following', [FollowController::class, 'following']);

        Route::post('activities/{activity}/likes', [LikeController::class, 'toggle']);
        Route::get('activities/{activity}/likes', [LikeController::class, 'index']);

        Route::get('activities/{activity}/comments', [CommentController::class, 'index']);
        Route::post('activities/{activity}/comments', [CommentController::class, 'store']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

        Route::prefix('feed')->group(function () {
            Route::get('following', [FeedController::class, 'following']);
            Route::get('nearby', [FeedController::class, 'nearby']);
            Route::get('trending', [FeedController::class, 'trending']);
        });

        Route::prefix('challenges')->group(function () {
            Route::get('/', [ChallengeController::class, 'index']);
            Route::post('/', [ChallengeController::class, 'store']);
            Route::get('my', [ChallengeController::class, 'myChallenges']);
            Route::get('available', [ChallengeController::class, 'available']);
            Route::get('{challenge}', [ChallengeController::class, 'show']);
            Route::put('{challenge}', [ChallengeController::class, 'update']);
            Route::delete('{challenge}', [ChallengeController::class, 'destroy']);
            Route::post('{challenge}/join', [ChallengeController::class, 'join']);
            Route::delete('{challenge}/leave', [ChallengeController::class, 'leave']);
            Route::get('{challenge}/leaderboard', [ChallengeController::class, 'leaderboard']);
        });
    });
});
