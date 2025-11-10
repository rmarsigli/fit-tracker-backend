<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Social;

use App\Data\Social\CommentData;
use App\Http\Controllers\Controller;
use App\Models\Activity\Activity;
use App\Models\Social\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Activity $activity): JsonResponse
    {
        $authUser = auth()->user();

        $comments = $activity->comments()
            ->with('user')
            ->latest()
            ->paginate(20);

        $data = $comments->map(function ($comment) use ($authUser) {
            return CommentData::from($comment, $authUser);
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $comment = Comment::create([
            'activity_id' => $activity->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'data' => CommentData::from($comment->load('user'), auth()->user()),
        ], 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $user = auth()->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You can only delete your own comments',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
