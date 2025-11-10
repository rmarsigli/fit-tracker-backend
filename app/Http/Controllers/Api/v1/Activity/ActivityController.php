<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Activity;

use App\Data\Activity\ActivityData;
use App\Http\Controllers\Controller;
use App\Models\Activity\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

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
    public function index(Request $request): JsonResponse
    {
        $activities = Activity::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->paginate(20);

        return response()->json([
            'data' => ActivityData::collect($activities->items(), DataCollection::class),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Create activity
     *
     * Manually create a new activity (alternative to using the tracking endpoints).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = ActivityData::validateAndCreate($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Spatie\LaravelData\Exceptions\CannotCastEnum $e) {
            return response()->json([
                'message' => 'Tipo de atividade inválido',
                'errors' => ['type' => ['O tipo de atividade fornecido não é válido.']],
            ], 422);
        } catch (\ArgumentCountError $e) {
            return response()->json([
                'message' => 'Campos obrigatórios ausentes',
                'errors' => ['message' => ['Os campos obrigatórios não foram fornecidos.']],
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao validar dados',
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }

        $dataArray = $data->except('id', 'created_at', 'updated_at', 'distance_km', 'duration_formatted', 'avg_pace_min_km')->toArray();

        // Remove Optional instances - convert to null
        $dataArray = array_map(
            fn ($value) => $value instanceof Optional ? null : $value,
            $dataArray
        );

        // Remove null values to let database defaults work
        $dataArray = array_filter($dataArray, fn ($value) => $value !== null);

        $activity = Activity::create([
            ...$dataArray,
            'user_id' => $request->user()->id,
        ]);

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade criada com sucesso',
            'data' => ActivityData::from($activity),
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
            'data' => ActivityData::from($activity),
        ]);
    }

    /**
     * Update activity
     *
     * Update activity details like title, description, or visibility.
     */
    public function update(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        try {
            $data = ActivityData::from($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Spatie\LaravelData\Exceptions\CannotCastEnum $e) {
            return response()->json([
                'message' => 'Tipo de atividade inválido',
                'errors' => ['type' => ['O tipo de atividade fornecido não é válido.']],
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao validar dados',
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }

        $dataArray = $data->except('id', 'created_at', 'updated_at', 'distance_km', 'duration_formatted', 'avg_pace_min_km')->toArray();

        // Remove Optional instances - convert to null
        $dataArray = array_map(
            fn ($value) => $value instanceof Optional ? null : $value,
            $dataArray
        );

        // Remove null values for update (only update fields that were provided)
        $dataArray = array_filter($dataArray, fn ($value) => $value !== null);

        $activity->update($dataArray);

        $activity->load('user');

        return response()->json([
            'message' => 'Atividade atualizada com sucesso',
            'data' => ActivityData::from($activity),
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
