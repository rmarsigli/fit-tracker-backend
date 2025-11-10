<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Social;

use App\Http\Controllers\Controller;
use App\Models\Activity\Activity;
use App\Models\Social\Like;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function toggle(Activity $activity): JsonResponse
    {
        $user = auth()->user();

        $like = Like::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json([
                'message' => 'Like removed successfully',
                'liked' => false,
                'likes_count' => $activity->fresh()->likesCount(),
            ]);
        }

        Like::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Activity liked successfully',
            'liked' => true,
            'likes_count' => $activity->fresh()->likesCount(),
        ], 201);
    }

    public function index(Activity $activity): JsonResponse
    {
        $likes = $activity->likes()
            ->with('user')
            ->latest()
            ->paginate(50);

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Like> $likes */
        return response()->json([
            'data' => $likes->map(function (Like $like) {
                /** @var User $user */
                $user = $like->user;

                return [
                    'id' => $like->id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                    ],
                    'created_at' => $like->created_at->toISOString(),
                ];
            }),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
            ],
        ]);
    }
}
