<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FeedService
{
    public function getFollowingFeed(User $user, int $limit = 20): Collection
    {
        $cacheKey = "feed:following:{$user->id}:{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($user, $limit) {
            $followingIds = $user->following()
                ->pluck('following_id')
                ->toArray();

            if (empty($followingIds)) {
                return collect();
            }

            return Activity::query()
                ->with(['user', 'segmentEfforts.segment'])
                ->whereIn('user_id', $followingIds)
                ->where('visibility', 'public')
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->limit($limit)
                ->get();
        });
    }

    public function getNearbyFeed(float $lat, float $lng, int $radiusKm = 10, int $limit = 20): Collection
    {
        $cacheKey = "feed:nearby:{$lat}:{$lng}:{$radiusKm}:{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($lat, $lng, $radiusKm, $limit) {
            $radiusMeters = $radiusKm * 1000;

            return Activity::query()
                ->with(['user', 'segmentEfforts.segment'])
                ->whereNotNull('route')
                ->whereNotNull('completed_at')
                ->where('visibility', 'public')
                ->whereRaw(
                    'ST_DWithin(route::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                    [$lng, $lat, $radiusMeters]
                )
                ->latest('completed_at')
                ->limit($limit)
                ->get();
        });
    }

    public function getTrendingFeed(int $days = 7, int $limit = 20): Collection
    {
        $cacheKey = "feed:trending:{$days}:{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($days, $limit) {
            return Activity::query()
                ->with(['user', 'segmentEfforts.segment', 'likes'])
                ->where('visibility', 'public')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDays($days))
                ->whereHas('likes')
                ->withCount('likes')
                ->orderByDesc('likes_count')
                ->orderByDesc('completed_at')
                ->limit($limit)
                ->get();
        });
    }

    public function clearFeedCache(User $user): void
    {
        Cache::forget("feed:following:{$user->id}");
    }

    public function clearAllFeedCaches(): void
    {
        Cache::flush();
    }
}
