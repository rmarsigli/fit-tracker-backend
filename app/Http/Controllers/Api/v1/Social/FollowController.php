<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Social;

use App\Data\User\UserProfileData;
use App\Http\Controllers\Controller;
use App\Models\Social\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    public function follow(User $user): JsonResponse
    {
        $authUser = auth()->user();

        if ($authUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot follow yourself',
            ], 400);
        }

        if ($authUser->isFollowing($user)) {
            return response()->json([
                'message' => 'Already following this user',
            ], 409);
        }

        Follow::create([
            'follower_id' => $authUser->id,
            'following_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Successfully followed user',
            'user' => UserProfileData::from($user->fresh(), $authUser),
        ], 201);
    }

    public function unfollow(User $user): JsonResponse
    {
        $authUser = auth()->user();

        $follow = Follow::where('follower_id', $authUser->id)
            ->where('following_id', $user->id)
            ->first();

        if (! $follow) {
            return response()->json([
                'message' => 'You are not following this user',
            ], 404);
        }

        $follow->delete();

        return response()->json([
            'message' => 'Successfully unfollowed user',
        ]);
    }

    public function followers(User $user): JsonResponse
    {
        $authUser = auth()->user();

        $followers = $user->followers()
            ->with('follower')
            ->latest()
            ->paginate(20);

        $data = $followers->pluck('follower')->map(function ($follower) use ($authUser) {
            return UserProfileData::from($follower, $authUser);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $followers->currentPage(),
                'last_page' => $followers->lastPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
            ],
        ]);
    }

    public function following(User $user): JsonResponse
    {
        $authUser = auth()->user();

        $following = $user->following()
            ->with('following')
            ->latest()
            ->paginate(20);

        $data = $following->pluck('following')->map(function ($followed) use ($authUser) {
            return UserProfileData::from($followed, $authUser);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $following->currentPage(),
                'last_page' => $following->lastPage(),
                'per_page' => $following->perPage(),
                'total' => $following->total(),
            ],
        ]);
    }
}
