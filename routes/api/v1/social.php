<?php

use App\Http\Controllers\Api\v1\Social\FollowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('users/{user}/follow', [FollowController::class, 'follow']);
    Route::delete('users/{user}/unfollow', [FollowController::class, 'unfollow']);
    Route::get('users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('users/{user}/following', [FollowController::class, 'following']);
});
