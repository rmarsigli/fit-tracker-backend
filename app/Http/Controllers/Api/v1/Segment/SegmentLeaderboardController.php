<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Segment;

use App\Http\Controllers\Controller;
use App\Models\Segment\Segment;
use App\Models\User;
use App\Services\Segment\SegmentMatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Segment Leaderboards
 *
 * Manage segment leaderboards, personal records, and KOM/QOM achievements.
 */
class SegmentLeaderboardController extends Controller
{
    public function __construct(
        private readonly SegmentMatcherService $segmentMatcher
    ) {}

    /**
     * Get segment leaderboard
     *
     * Returns top efforts for a segment, ordered by fastest time.
     * Shows the best effort for each unique user.
     */
    public function index(Segment $segment): JsonResponse
    {
        $leaderboard = $this->segmentMatcher->getLeaderboard($segment, limit: 20);

        $data = $leaderboard->map(
            /**
             * @param \App\Models\Segment\SegmentEffort $effort
             * @param int $index
             * @return array{rank: int, user: array{id: int, name: string, username: string, avatar: string|null}, elapsed_time_seconds: float, elapsed_time_formatted: string, average_speed_kmh: float, average_pace_min_km: string|null, achieved_at: string|null, is_kom: bool, is_pr: bool}
             */
            function (mixed $effort, mixed $index): array {
                /** @var \App\Models\User $user */
                $user = $effort->user;

                /** @var \Illuminate\Support\Carbon|null $achievedAt */
                $achievedAt = $effort->achieved_at;

            return [
                'rank' => $index + 1,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                ],
                'elapsed_time_seconds' => $effort->duration_seconds,
                'elapsed_time_formatted' => gmdate('H:i:s', (int) $effort->duration_seconds),
                'average_speed_kmh' => round($effort->avg_speed_kmh, 2),
                'average_pace_min_km' => $effort->avg_speed_kmh > 0
                    ? gmdate('i:s', (int) (60 / $effort->avg_speed_kmh))
                    : null,
                'achieved_at' => $achievedAt !== null ? $achievedAt->toISOString() : null,
                'is_kom' => $effort->is_kom,
                'is_pr' => $effort->is_pr,
            ];
            }
        );

        return response()->json([
            'data' => $data,
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
                'distance_km' => round($segment->distance_meters / 1000, 2),
                'total_efforts' => $segment->efforts()->count(),
            ],
        ]);
    }

    /**
     * Get user personal records
     *
     * Returns all segments where user has efforts, showing their best time and rank.
     * Requires authentication for /me/records route.
     */
    public function userRecords(Request $request, ?User $user = null): JsonResponse
    {
        $user = $user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $records = $this->segmentMatcher->getUserPersonalRecords($user->id);

        $data = $records->map(
            /**
             * @param \App\Models\Segment\SegmentEffort $effort
             * @return array<string, mixed>
             */
            function (mixed $effort): array {
                /** @var \App\Models\Segment\Segment $segment */
                $segment = $effort->segment;

                /** @var \Illuminate\Support\Carbon|null $achievedAt */
                $achievedAt = $effort->achieved_at;

                /** @var \App\Enums\Segment\SegmentType $segmentType */
                $segmentType = $segment->type;

                return [
                    'segment' => [
                        'id' => $segment->id,
                        'name' => $segment->name,
                        'distance_km' => round($segment->distance_meters / 1000, 2),
                        'type' => $segmentType->value,
                    ],
                    'personal_record' => [
                        'elapsed_time_seconds' => $effort->duration_seconds,
                        'elapsed_time_formatted' => gmdate('H:i:s', (int) $effort->duration_seconds),
                        'average_speed_kmh' => round($effort->avg_speed_kmh, 2),
                        'achieved_at' => $achievedAt !== null ? $achievedAt->toISOString() : null,
                        'is_kom' => $effort->is_kom,
                    ],
                    'rank' => $effort->rank_overall ?? null,
                    'total_attempts' => $segment->efforts()
                        ->where('user_id', $effort->user_id)
                        ->count(),
                ];
            }
        );

        return response()->json([
            'data' => $data,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
        ]);
    }

    /**
     * Get current KOM holder
     *
     * Returns the current KOM (King of Mountain) holder for a segment.
     * KOM is the fastest male athlete on the segment.
     */
    public function kom(Segment $segment): JsonResponse
    {
        /** @var \App\Models\Segment\SegmentEffort|null $kom */
        $kom = $segment->efforts()
            ->where('is_kom', true)
            ->with('user:id,name,username,avatar,gender')
            ->whereHas('user', fn ($q) => $q->where('gender', 'male'))
            ->orderBy('duration_seconds')
            ->first();

        if (! $kom) {
            return response()->json([
                'data' => null,
                'message' => 'No KOM recorded for this segment yet',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = $kom->user;

        /** @var \Illuminate\Support\Carbon|null $achievedAt */
        $achievedAt = $kom->achieved_at;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                ],
                'elapsed_time_seconds' => $kom->duration_seconds,
                'elapsed_time_formatted' => gmdate('H:i:s', (int) $kom->duration_seconds),
                'average_speed_kmh' => round($kom->avg_speed_kmh, 2),
                'achieved_at' => $achievedAt !== null ? $achievedAt->toISOString() : null,
            ],
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
            ],
        ]);
    }

    /**
     * Get current QOM holder
     *
     * Returns the current QOM (Queen of Mountain) holder for a segment.
     * QOM is the fastest female athlete on the segment.
     */
    public function qom(Segment $segment): JsonResponse
    {
        /** @var \App\Models\Segment\SegmentEffort|null $qom */
        $qom = $segment->efforts()
            ->where('is_kom', true)
            ->with('user:id,name,username,avatar,gender')
            ->whereHas('user', fn ($q) => $q->where('gender', 'female'))
            ->orderBy('duration_seconds')
            ->first();

        if (! $qom) {
            return response()->json([
                'data' => null,
                'message' => 'No QOM recorded for this segment yet',
            ]);
        }

        /** @var \App\Models\User $user */
        $user = $qom->user;

        /** @var \Illuminate\Support\Carbon|null $achievedAt */
        $achievedAt = $qom->achieved_at;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                ],
                'elapsed_time_seconds' => $qom->duration_seconds,
                'elapsed_time_formatted' => gmdate('H:i:s', (int) $qom->duration_seconds),
                'average_speed_kmh' => round($qom->avg_speed_kmh, 2),
                'achieved_at' => $achievedAt !== null ? $achievedAt->toISOString() : null,
            ],
            'segment' => [
                'id' => $segment->id,
                'name' => $segment->name,
            ],
        ]);
    }
}
