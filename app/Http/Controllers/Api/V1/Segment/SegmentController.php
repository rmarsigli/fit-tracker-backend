<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Segment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Segment\StoreSegmentRequest;
use App\Http\Requests\Api\V1\Segment\UpdateSegmentRequest;
use App\Http\Resources\Api\V1\Segment\SegmentCollection;
use App\Http\Resources\Api\V1\Segment\SegmentResource;
use App\Models\Segment\Segment;
use App\Services\PostGIS\GeoQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function __construct(
        protected GeoQueryService $geoQuery
    ) {}

    public function index(Request $request): SegmentCollection
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

        return new SegmentCollection($segments);
    }

    public function store(StoreSegmentRequest $request): JsonResponse
    {
        $segment = Segment::create([
            ...$request->validated(),
            'creator_id' => $request->user()->id,
        ]);

        $segment->load('creator');

        return response()->json([
            'message' => 'Segmento criado com sucesso',
            'data' => new SegmentResource($segment),
        ], 201);
    }

    public function show(Segment $segment): JsonResponse
    {
        $segment->load('creator');

        return response()->json([
            'data' => new SegmentResource($segment),
        ]);
    }

    public function update(UpdateSegmentRequest $request, Segment $segment): JsonResponse
    {
        if ($segment->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para editar este segmento',
            ], 403);
        }

        $segment->update($request->validated());
        $segment->load('creator');

        return response()->json([
            'message' => 'Segmento atualizado com sucesso',
            'data' => new SegmentResource($segment),
        ]);
    }

    public function destroy(Request $request, Segment $segment): JsonResponse
    {
        if ($segment->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para deletar este segmento',
            ], 403);
        }

        $segment->delete();

        return response()->json([
            'message' => 'Segmento deletado com sucesso',
        ]);
    }

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
            'data' => SegmentResource::collection($segments),
            'meta' => [
                'total' => $segments->count(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_meters' => $radius,
            ],
        ]);
    }
}
