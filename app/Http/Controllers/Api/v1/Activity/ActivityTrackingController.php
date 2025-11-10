<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Activity;

use App\Data\Activity\ActivityData;
use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Http\Controllers\Controller;
use App\Services\Activity\ActivityTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Activity Tracking
 *
 * Real-time GPS activity tracking endpoints for recording runs, rides, and workouts.
 * Activities are stored in Redis during tracking and saved to the database when finished.
 */
class ActivityTrackingController extends Controller
{
    public function __construct(
        private readonly ActivityTrackingService $trackingService
    ) {}

    /**
     * Start tracking activity
     *
     * Begin a new activity tracking session. Returns an activity_id to use for subsequent tracking calls.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(ActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $activityId = $this->trackingService->startActivity(
            $request->user(),
            ActivityType::from($validated['type']),
            $validated['title']
        );

        return response()->json([
            'message' => 'Atividade iniciada com sucesso',
            'activity_id' => $activityId,
        ], 201);
    }

    /**
     * Track GPS location
     *
     * Record a GPS point for an active tracking session. Call this endpoint repeatedly as the user moves.
     */
    public function track(Request $request, string $activityId): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'integer', 'min:30', 'max:300'],
        ]);

        $success = $this->trackingService->trackLocation(
            $activityId,
            $validated['latitude'],
            $validated['longitude'],
            $validated['altitude'] ?? null,
            $validated['heart_rate'] ?? null
        );

        if (! $success) {
            return response()->json([
                'message' => 'Não foi possível registrar a localização',
            ], 400);
        }

        return response()->json([
            'message' => 'Localização registrada com sucesso',
        ]);
    }

    /**
     * Pause activity tracking
     *
     * Pause the current activity. Paused time will not be counted in moving time.
     */
    public function pause(string $activityId): JsonResponse
    {
        $success = $this->trackingService->pauseActivity($activityId);

        if (! $success) {
            return response()->json([
                'message' => 'Não foi possível pausar a atividade',
            ], 400);
        }

        return response()->json([
            'message' => 'Atividade pausada com sucesso',
        ]);
    }

    /**
     * Resume activity tracking
     *
     * Resume a paused activity to continue tracking.
     */
    public function resume(string $activityId): JsonResponse
    {
        $success = $this->trackingService->resumeActivity($activityId);

        if (! $success) {
            return response()->json([
                'message' => 'Não foi possível retomar a atividade',
            ], 400);
        }

        return response()->json([
            'message' => 'Atividade retomada com sucesso',
        ]);
    }

    /**
     * Finish activity tracking
     *
     * Complete the activity and save it to the database. Calculates all statistics and triggers segment matching.
     */
    public function finish(Request $request, string $activityId): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'string', Rule::enum(ActivityVisibility::class)],
        ]);

        $activity = $this->trackingService->finishActivity(
            $activityId,
            $validated['description'] ?? null,
            isset($validated['visibility']) ? ActivityVisibility::from($validated['visibility']) : null
        );

        if (! $activity) {
            return response()->json([
                'message' => 'Não foi possível finalizar a atividade. Verifique se há pontos GPS suficientes.',
            ], 400);
        }

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade finalizada com sucesso',
            'data' => ActivityData::from($activity),
        ]);
    }

    /**
     * Get tracking status
     *
     * Get the current status of an active tracking session.
     */
    public function status(string $activityId): JsonResponse
    {
        $data = $this->trackingService->getTrackingData($activityId);

        if (! $data) {
            return response()->json([
                'message' => 'Atividade não encontrada',
            ], 404);
        }

        return response()->json([
            'data' => [
                'activity_id' => $activityId,
                'status' => $data['status'],
                'type' => $data['type'],
                'title' => $data['title'],
                'started_at' => $data['started_at'],
                'points_count' => count($data['points']),
            ],
        ]);
    }
}
