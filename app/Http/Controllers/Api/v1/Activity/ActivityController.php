<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\Activity\ActivityCollection;
use App\Http\Resources\Activity\ActivityResource;
use App\Models\Activity\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Activities
 *
 * Manage completed activities. Activities are GPS-tracked workouts with statistics like distance, pace, and elevation.
 */
class ActivityController extends Controller
{
    /**
     * List activities
     *
     * Get a paginated list of the authenticated user's activities.
     */
    public function index(Request $request): ActivityCollection
    {
        $activities = Activity::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->paginate(20);

        return new ActivityCollection($activities);
    }

    /**
     * Create activity
     *
     * Manually create a new activity (alternative to using the tracking endpoints).
     */
    public function store(StoreActivityRequest $request): JsonResponse
    {
        $activity = Activity::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade criada com sucesso',
            'data' => new ActivityResource($activity),
        ], 201);
    }

    /**
     * Get activity
     *
     * Retrieve a specific activity with all details including segment efforts.
     */
    public function show(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        $activity->load('user', 'segmentEfforts');

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Update activity
     *
     * Update activity details like title, description, or visibility.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->update($request->validated());

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade atualizada com sucesso',
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Delete activity
     *
     * Permanently delete an activity.
     */
    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return response()->json([
            'message' => 'Atividade excluída com sucesso',
        ]);
    }
}
