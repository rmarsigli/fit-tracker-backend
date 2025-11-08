<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Activity\StoreActivityRequest;
use App\Http\Requests\Api\V1\Activity\UpdateActivityRequest;
use App\Http\Resources\Api\V1\Activity\ActivityCollection;
use App\Http\Resources\Api\V1\Activity\ActivityResource;
use App\Models\Activity\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): ActivityCollection
    {
        $activities = Activity::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->paginate(20);

        return new ActivityCollection($activities);
    }

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

    public function show(Request $request, Activity $activity): JsonResponse
    {
        if ($activity->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar esta atividade',
            ], 403);
        }

        $activity->load('user', 'segmentEfforts');

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        if ($activity->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar esta atividade',
            ], 403);
        }

        $activity->update($request->validated());

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade atualizada com sucesso',
            'data' => new ActivityResource($activity),
        ]);
    }

    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        if ($activity->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir esta atividade',
            ], 403);
        }

        $activity->delete();

        return response()->json([
            'message' => 'Atividade excluída com sucesso',
        ]);
    }
}
