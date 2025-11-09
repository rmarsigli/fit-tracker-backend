<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Activity;

use App\Http\Controllers\Controller;
use App\Http\Resources\Activity\ActivityResource;
use App\Models\Activity\Activity;
use App\Services\Activity\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Statistics
 *
 * Activity statistics and analytics endpoints including splits, pace zones, and user statistics.
 */
class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService
    ) {}

    /**
     * Get activity splits
     *
     * Calculate kilometer/mile splits for an activity showing pace progression throughout the workout.
     */
    public function activitySplits(Activity $activity): JsonResponse
    {
        $splits = $this->statisticsService->calculateSplits($activity);

        return response()->json([
            'data' => [
                'activity_id' => $activity->id,
                'splits' => $splits,
            ],
        ]);
    }

    /**
     * Get pace zones
     *
     * Calculate training pace zones based on the activity's average pace.
     */
    public function activityPaceZones(Activity $activity): JsonResponse
    {
        $paceZones = $this->statisticsService->calculatePaceZones($activity);

        return response()->json([
            'data' => [
                'activity_id' => $activity->id,
                'pace_zones' => $paceZones,
            ],
        ]);
    }

    /**
     * Get user statistics
     *
     * Retrieve comprehensive statistics for the authenticated user including totals, averages, and recent activity.
     */
    public function userStats(Request $request): JsonResponse
    {
        $stats = $this->statisticsService->getUserStats($request->user());

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get public activity feed
     *
     * Retrieve recent public activities from all users.
     */
    public function activityFeed(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $limit = min($limit, 100);

        $activities = $this->statisticsService->getActivityFeed($request->user(), $limit);

        return response()->json([
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'count' => $activities->count(),
            ],
        ]);
    }
}
