<?php

declare(strict_types=1);

use App\Http\Controllers\Api\v1\Activity\ActivityController;
use App\Http\Controllers\Api\v1\Activity\ActivityTrackingController;
use App\Http\Controllers\Api\v1\Activity\StatisticsController;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\Segment\SegmentController;
use Illuminate\Support\Facades\Route;

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
    });
});
