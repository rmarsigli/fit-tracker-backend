<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Activity\ActivityResource;
use App\Services\Activity\ActivityTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityTrackingController extends Controller
{
    public function __construct(
        private readonly ActivityTrackingService $trackingService
    ) {}

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
            'data' => new ActivityResource($activity),
        ]);
    }

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
