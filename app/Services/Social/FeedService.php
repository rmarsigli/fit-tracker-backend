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
        $cacheKey = "feed:following:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user, $limit) {
            $followingIds = $user->following()
                ->pluck('following_id')
                ->toArray();

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

    public function clearFeedCache(User $user): void
    {
        Cache::forget("feed:following:{$user->id}");
    }
}
