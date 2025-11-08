<?php declare(strict_types=1);

namespace App\Services\Segment;

use App\Models\Activity\Activity;
use App\Models\Segment\Segment;
use App\Models\Segment\SegmentEffort;
use App\Services\PostGIS\GeoQueryService;
use Illuminate\Support\Collection;

class SegmentMatcherService
{
    public function __construct(
        protected GeoQueryService $geoQuery
    ) {}

    /**
     * Detect and process all segment matches for an activity
     *
     * @return Collection<int, SegmentEffort>
     */
    public function processActivity(Activity $activity, float $minOverlapPercentage = 90.0): Collection
    {
        if (! $activity->route || ! $activity->completed_at) {
            return collect();
        }

        $intersectingSegments = $this->geoQuery->findIntersectingSegments(
            $activity,
            $minOverlapPercentage
        );

        $efforts = collect();

        foreach ($intersectingSegments as $match) {
            /** @var Segment $segment */
            $segment = $match['segment'];

            $effort = $this->createSegmentEffort($activity, $segment, $match);

            if ($effort) {
                $efforts->push($effort);
            }
        }

        return $efforts;
    }

    /**
     * Create a segment effort for an activity on a specific segment
     */
    protected function createSegmentEffort(Activity $activity, Segment $segment, array $matchData): ?SegmentEffort
    {
        $durationSeconds = $this->estimateSegmentDuration($activity, $segment, $matchData);

        if (! $durationSeconds || $durationSeconds <= 0) {
            return null;
        }

        $avgSpeedKmh = ($segment->distance_meters / 1000) / ($durationSeconds / 3600);

        $effort = SegmentEffort::create([
            'segment_id' => $segment->id,
            'activity_id' => $activity->id,
            'user_id' => $activity->user_id,
            'duration_seconds' => $durationSeconds,
            'avg_speed_kmh' => $avgSpeedKmh,
            'avg_heart_rate' => $activity->avg_heart_rate,
            'achieved_at' => $activity->completed_at,
            'is_pr' => false,
            'is_kom' => false,
        ]);

        $this->updatePersonalRecord($effort);
        $this->updateRankings($effort);
        $this->updateKomQom($effort);

        $segment->increment('total_attempts');

        $uniqueAthletes = SegmentEffort::where('segment_id', $segment->id)
            ->distinct('user_id')
            ->count('user_id');

        $segment->update(['unique_athletes' => $uniqueAthletes]);

        return $effort->fresh();
    }

    /**
     * Estimate the duration it took to complete the segment
     */
    protected function estimateSegmentDuration(Activity $activity, Segment $segment, array $matchData): ?int
    {
        if (! $activity->duration_seconds || $activity->duration_seconds <= 0) {
            return null;
        }

        $overlapPercentage = $matchData['overlap_percentage'];
        $activityDistance = $activity->distance_meters;

        if ($activityDistance <= 0) {
            return null;
        }

        $segmentRatio = ($segment->distance_meters * ($overlapPercentage / 100)) / $activityDistance;

        $estimatedDuration = (int) round($activity->duration_seconds * $segmentRatio);

        return max(1, $estimatedDuration);
    }

    /**
     * Check if this effort is a personal record and update accordingly
     */
    protected function updatePersonalRecord(SegmentEffort $effort): void
    {
        $previousBest = SegmentEffort::where('segment_id', $effort->segment_id)
            ->where('user_id', $effort->user_id)
            ->where('id', '!=', $effort->id)
            ->orderBy('duration_seconds', 'asc')
            ->first();

        if (! $previousBest || $effort->duration_seconds < $previousBest->duration_seconds) {
            SegmentEffort::where('segment_id', $effort->segment_id)
                ->where('user_id', $effort->user_id)
                ->update(['is_pr' => false]);

            $effort->update(['is_pr' => true]);
        }
    }

    /**
     * Update the overall and age group rankings for this effort
     */
    protected function updateRankings(SegmentEffort $effort): void
    {
        $allEfforts = SegmentEffort::where('segment_id', $effort->segment_id)
            ->orderBy('duration_seconds', 'asc')
            ->get()
            ->unique('user_id')
            ->values();

        foreach ($allEfforts as $index => $segmentEffort) {
            $segmentEffort->update(['rank_overall' => $index + 1]);
        }
    }

    /**
     * Check and update KOM/QOM status for this segment
     */
    protected function updateKomQom(SegmentEffort $effort): void
    {
        $fastestEffort = SegmentEffort::where('segment_id', $effort->segment_id)
            ->orderBy('duration_seconds', 'asc')
            ->first();

        if (! $fastestEffort) {
            return;
        }

        SegmentEffort::where('segment_id', $effort->segment_id)
            ->update(['is_kom' => false]);

        $fastestEffort->update(['is_kom' => true]);
    }

    /**
     * Get leaderboard for a specific segment
     *
     * @return Collection<int, SegmentEffort>
     */
    public function getLeaderboard(Segment $segment, int $limit = 10): Collection
    {
        return SegmentEffort::where('segment_id', $segment->id)
            ->with(['user', 'activity'])
            ->orderBy('duration_seconds', 'asc')
            ->limit($limit)
            ->get()
            ->unique('user_id')
            ->values();
    }

    /**
     * Get user's personal records on segments
     *
     * @return Collection<int, SegmentEffort>
     */
    public function getUserPersonalRecords(int $userId, int $limit = 20): Collection
    {
        return SegmentEffort::where('user_id', $userId)
            ->where('is_pr', true)
            ->with(['segment', 'activity'])
            ->orderBy('achieved_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's KOM/QOM achievements
     *
     * @return Collection<int, SegmentEffort>
     */
    public function getUserKomQomAchievements(int $userId): Collection
    {
        return SegmentEffort::where('user_id', $userId)
            ->where('is_kom', true)
            ->with(['segment', 'activity'])
            ->orderBy('achieved_at', 'desc')
            ->get();
    }
}
