<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Challenge;

use App\Data\Challenge\ChallengeData;
use App\Data\Challenge\ChallengeParticipantData;
use App\Http\Controllers\Controller;
use App\Models\Challenge\Challenge;
use App\Services\Challenge\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function __construct(private readonly ChallengeService $challengeService) {}

    public function index(Request $request): JsonResponse
    {
        $challenges = Challenge::query()
            ->where('is_public', true)
            ->withCount('participants')
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => ChallengeData::collect($challenges->items()),
            'meta' => [
                'current_page' => $challenges->currentPage(),
                'last_page' => $challenges->lastPage(),
                'per_page' => $challenges->perPage(),
                'total' => $challenges->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = ChallengeData::from($request->all());

        $challenge = Challenge::create([
            'created_by' => $request->user()->id,
            'name' => $data->name,
            'description' => $data->description,
            'type' => $data->type,
            'goal_value' => $data->goal_value,
            'goal_unit' => $data->goal_unit,
            'starts_at' => $data->starts_at,
            'ends_at' => $data->ends_at,
            'is_public' => $data->is_public,
            'max_participants' => $data->max_participants,
        ]);

        $challenge->load('creator:id,name');
        $challenge->loadCount('participants');

        return response()->json([
            'data' => ChallengeData::from($challenge),
        ], 201);
    }

    public function show(Challenge $challenge): JsonResponse
    {
        $challenge->load('creator:id,name');
        $challenge->loadCount('participants');

        return response()->json([
            'data' => ChallengeData::from($challenge),
        ]);
    }

    public function update(Request $request, Challenge $challenge): JsonResponse
    {
        if ($challenge->created_by !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to update this challenge',
            ], 403);
        }

        $data = ChallengeData::from($request->all());

        $challenge->update([
            'name' => $data->name,
            'description' => $data->description,
            'type' => $data->type,
            'goal_value' => $data->goal_value,
            'goal_unit' => $data->goal_unit,
            'starts_at' => $data->starts_at,
            'ends_at' => $data->ends_at,
            'is_public' => $data->is_public,
            'max_participants' => $data->max_participants,
        ]);

        $challenge->load('creator:id,name');
        $challenge->loadCount('participants');

        return response()->json([
            'data' => ChallengeData::from($challenge),
        ]);
    }

    public function destroy(Request $request, Challenge $challenge): JsonResponse
    {
        if ($challenge->created_by !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to delete this challenge',
            ], 403);
        }

        $challenge->delete();

        return response()->json(null, 204);
    }

    public function join(Request $request, Challenge $challenge): JsonResponse
    {
        try {
            $participant = $this->challengeService->joinChallenge($challenge, $request->user());

            return response()->json([
                'data' => ChallengeParticipantData::from($participant),
                'message' => 'Successfully joined the challenge',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function leave(Request $request, Challenge $challenge): JsonResponse
    {
        try {
            $this->challengeService->leaveChallenge($challenge, $request->user());

            return response()->json([
                'message' => 'Successfully left the challenge',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function leaderboard(Challenge $challenge): JsonResponse
    {
        $leaderboard = $this->challengeService->getLeaderboard($challenge);

        return response()->json([
            'data' => ChallengeParticipantData::collect($leaderboard),
        ]);
    }

    public function myChallenges(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $challenges = $this->challengeService->getUserChallenges($request->user(), $status);

        return response()->json([
            'data' => ChallengeData::collect($challenges),
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        $challenges = $this->challengeService->getAvailableChallenges($request->user());

        return response()->json([
            'data' => ChallengeData::collect($challenges),
        ]);
    }
}
