<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1\Social;

use App\Data\Activity\ActivityData;
use App\Http\Controllers\Controller;
use App\Services\Social\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        protected FeedService $feedService
    ) {}

    public function following(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = min((int) $request->input('limit', 20), 50);

        $activities = $this->feedService->getFollowingFeed($user, $limit);

        return response()->json([
            'data' => $activities->map(fn ($activity) => ActivityData::from($activity)),
            'meta' => [
                'count' => $activities->count(),
                'limit' => $limit,
            ],
        ]);
    }

    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $radius = (int) ($validated['radius'] ?? 10);
        $limit = (int) ($validated['limit'] ?? 20);

        $activities = $this->feedService->getNearbyFeed(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $radius,
            $limit
        );

        return response()->json([
            'data' => $activities->map(fn ($activity) => ActivityData::from($activity)),
            'meta' => [
                'count' => $activities->count(),
                'radius_km' => $radius,
                'limit' => $limit,
            ],
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 50);
        $days = min((int) $request->input('days', 7), 30);

        $activities = $this->feedService->getTrendingFeed($days, $limit);

        return response()->json([
            'data' => $activities->map(fn ($activity) => ActivityData::from($activity)),
            'meta' => [
                'count' => $activities->count(),
                'days' => $days,
                'limit' => $limit,
            ],
        ]);
    }
}
