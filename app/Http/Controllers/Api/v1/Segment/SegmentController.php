<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Segment;

use App\Data\Segment\SegmentData;
use App\Http\Controllers\Controller;
use App\Models\Segment\Segment;
use App\Services\PostGIS\GeoQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

/**
 * @group Segments
 *
 * Segments are popular routes or portions of routes where athletes can compete for best times and rankings.
 */
class SegmentController extends Controller
{
    public function __construct(
        protected GeoQueryService $geoQuery
    ) {}

    /**
     * List segments
     *
     * Get a paginated list of segments with optional filtering by type or creator.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Segment::query()->with('creator');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('creator_id')) {
            $query->where('creator_id', $request->input('creator_id'));
        }

        if ($request->boolean('my_segments')) {
            $query->where('creator_id', $request->user()->id);
        }

        $segments = $query->latest()->paginate(20);

        return response()->json([
            'data' => SegmentData::collect($segments->items(), DataCollection::class),
            'meta' => [
                'current_page' => $segments->currentPage(),
                'last_page' => $segments->lastPage(),
                'per_page' => $segments->perPage(),
                'total' => $segments->total(),
            ],
        ]);
    }

    /**
     * Create segment
     *
     * Create a new segment from a GPS route.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = SegmentData::validateAndCreate($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Spatie\LaravelData\Exceptions\CannotCastEnum $e) {
            return response()->json([
                'message' => 'Tipo de segmento inválido',
                'errors' => ['type' => ['O tipo de segmento fornecido não é válido.']],
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

        $dataArray = $data->except('id', 'created_at', 'updated_at', 'distance_km')->toArray();

        // Remove Optional instances - convert to null
        $dataArray = array_map(
            fn ($value) => $value instanceof Optional ? null : $value,
            $dataArray
        );

        // Remove null values to let database defaults work
        $dataArray = array_filter($dataArray, fn ($value) => $value !== null);

        $segment = Segment::create([
            ...$dataArray,
            'creator_id' => $request->user()->id,
        ]);

        $segment->load('creator');

        return response()->json([
            'message' => 'Segmento criado com sucesso',
            'data' => SegmentData::from($segment),
        ], 201);
    }

    /**
     * Get segment
     *
     * Retrieve segment details including leaderboard information.
     */
    public function show(Segment $segment): JsonResponse
    {
        $segment->load('creator');

        return response()->json([
            'data' => SegmentData::from($segment),
        ]);
    }

    /**
     * Update segment
     *
     * Update segment details. Only the creator can update a segment.
     */
    public function update(Request $request, Segment $segment): JsonResponse
    {
        $this->authorize('update', $segment);

        try {
            $data = SegmentData::from($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Spatie\LaravelData\Exceptions\CannotCastEnum $e) {
            return response()->json([
                'message' => 'Tipo de segmento inválido',
                'errors' => ['type' => ['O tipo de segmento fornecido não é válido.']],
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao validar dados',
                'errors' => ['message' => [$e->getMessage()]],
            ], 422);
        }

        $dataArray = $data->except('id', 'created_at', 'updated_at', 'distance_km')->toArray();

        // Remove Optional instances - convert to null
        $dataArray = array_map(
            fn ($value) => $value instanceof Optional ? null : $value,
            $dataArray
        );

        // Remove null values to let database defaults work
        $dataArray = array_filter($dataArray, fn ($value) => $value !== null);

        $segment->update($dataArray);
        $segment->load('creator');

        return response()->json([
            'message' => 'Segmento atualizado com sucesso',
            'data' => SegmentData::from($segment),
        ]);
    }

    /**
     * Delete segment
     *
     * Permanently delete a segment. Only the creator can delete a segment.
     */
    public function destroy(Request $request, Segment $segment): JsonResponse
    {
        $this->authorize('delete', $segment);

        $segment->delete();

        return response()->json([
            'message' => 'Segmento deletado com sucesso',
        ]);
    }

    /**
     * Find nearby segments
     *
     * Search for segments near a specific GPS coordinate within a given radius.
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:100|max:50000',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');
        $radius = (float) $request->input('radius', 5000.0);
        $limit = (int) $request->input('limit', 20);

        $segments = $this->geoQuery->findSegmentsNearPoint(
            $latitude,
            $longitude,
            $radius,
            $limit
        );

        return response()->json([
            'data' => SegmentData::collect($segments, DataCollection::class),
            'meta' => [
                'total' => $segments->count(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_meters' => $radius,
            ],
        ]);
    }
}
