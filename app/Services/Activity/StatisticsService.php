<?php declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function calculateSplits(Activity $activity): array
    {
        if (! $activity->raw_data || ! isset($activity->raw_data['points'])) {
            return [];
        }

        $points = $activity->raw_data['points'];

        if (count($points) < 2) {
            return [];
        }

        $splits = [];
        $currentDistance = 0;
        $currentSplitStart = 0;
        $splitDistance = 1000;

        for ($i = 1; $i < count($points); $i++) {
            $prevPoint = $points[$i - 1];
            $currPoint = $points[$i];

            $segmentDistance = $this->calculateDistance(
                $prevPoint['lat'],
                $prevPoint['lng'],
                $currPoint['lat'],
                $currPoint['lng']
            );

            $currentDistance += $segmentDistance;

            if ($currentDistance >= $splitDistance) {
                $splitNumber = count($splits) + 1;
                $splitStartTime = new \DateTime($points[$currentSplitStart]['timestamp']);
                $splitEndTime = new \DateTime($currPoint['timestamp']);
                $splitDuration = $splitEndTime->getTimestamp() - $splitStartTime->getTimestamp();

                $splits[] = [
                    'split' => $splitNumber,
                    'distance_meters' => $splitDistance,
                    'duration_seconds' => $splitDuration,
                    'pace_min_km' => $this->calculatePaceFromTime($splitDistance, $splitDuration),
                    'speed_kmh' => $splitDuration > 0 ? ($splitDistance / 1000) / ($splitDuration / 3600) : 0,
                ];

                $currentDistance -= $splitDistance;
                $currentSplitStart = $i;
            }
        }

        if ($currentDistance > 100 && $currentSplitStart < count($points) - 1) {
            $splitNumber = count($splits) + 1;
            $splitStartTime = new \DateTime($points[$currentSplitStart]['timestamp']);
            $splitEndTime = new \DateTime($points[count($points) - 1]['timestamp']);
            $splitDuration = $splitEndTime->getTimestamp() - $splitStartTime->getTimestamp();

            $splits[] = [
                'split' => $splitNumber,
                'distance_meters' => round($currentDistance, 2),
                'duration_seconds' => $splitDuration,
                'pace_min_km' => $this->calculatePaceFromTime($currentDistance, $splitDuration),
                'speed_kmh' => $splitDuration > 0 ? ($currentDistance / 1000) / ($splitDuration / 3600) : 0,
            ];
        }

        return $splits;
    }

    public function calculatePaceZones(Activity $activity): array
    {
        if (! $activity->avg_speed_kmh) {
            return [];
        }

        $avgPaceMinKm = 60 / $activity->avg_speed_kmh;

        $zones = [
            'recovery' => [
                'min_pace' => $avgPaceMinKm * 1.3,
                'max_pace' => $avgPaceMinKm * 1.5,
                'description' => 'Zona de Recuperação',
            ],
            'easy' => [
                'min_pace' => $avgPaceMinKm * 1.1,
                'max_pace' => $avgPaceMinKm * 1.3,
                'description' => 'Zona Leve',
            ],
            'moderate' => [
                'min_pace' => $avgPaceMinKm * 0.95,
                'max_pace' => $avgPaceMinKm * 1.1,
                'description' => 'Zona Moderada',
            ],
            'tempo' => [
                'min_pace' => $avgPaceMinKm * 0.85,
                'max_pace' => $avgPaceMinKm * 0.95,
                'description' => 'Zona de Tempo',
            ],
            'threshold' => [
                'min_pace' => $avgPaceMinKm * 0.75,
                'max_pace' => $avgPaceMinKm * 0.85,
                'description' => 'Zona de Limiar',
            ],
            'interval' => [
                'min_pace' => $avgPaceMinKm * 0.65,
                'max_pace' => $avgPaceMinKm * 0.75,
                'description' => 'Zona de Intervalos',
            ],
        ];

        foreach ($zones as $key => $zone) {
            $zones[$key]['min_pace_formatted'] = $this->formatPace($zone['min_pace']);
            $zones[$key]['max_pace_formatted'] = $this->formatPace($zone['max_pace']);
        }

        return $zones;
    }

    public function getUserStats(User $user): array
    {
        $activities = Activity::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->get();

        if ($activities->isEmpty()) {
            return $this->getEmptyStats();
        }

        $totalDistance = $activities->sum('distance_meters');
        $totalDuration = $activities->sum('duration_seconds');
        $totalElevationGain = $activities->sum('elevation_gain');
        $activitiesCount = $activities->count();

        $byType = $activities->groupBy('type')->map(function ($typeActivities) {
            return [
                'count' => $typeActivities->count(),
                'total_distance' => $typeActivities->sum('distance_meters'),
                'total_duration' => $typeActivities->sum('duration_seconds'),
                'avg_distance' => round($typeActivities->avg('distance_meters'), 2),
            ];
        });

        $last7Days = Activity::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(7))
            ->get();

        $last30Days = Activity::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->get();

        return [
            'total_activities' => $activitiesCount,
            'total_distance_meters' => $totalDistance,
            'total_distance_km' => round($totalDistance / 1000, 2),
            'total_duration_seconds' => $totalDuration,
            'total_duration_hours' => round($totalDuration / 3600, 2),
            'total_elevation_gain' => $totalElevationGain,
            'avg_distance_per_activity' => $activitiesCount > 0 ? round($totalDistance / $activitiesCount, 2) : 0,
            'avg_duration_per_activity' => $activitiesCount > 0 ? round($totalDuration / $activitiesCount, 2) : 0,
            'by_type' => $byType,
            'last_7_days' => [
                'count' => $last7Days->count(),
                'distance' => round($last7Days->sum('distance_meters') / 1000, 2),
                'duration' => $last7Days->sum('duration_seconds'),
            ],
            'last_30_days' => [
                'count' => $last30Days->count(),
                'distance' => round($last30Days->sum('distance_meters') / 1000, 2),
                'duration' => $last30Days->sum('duration_seconds'),
            ],
        ];
    }

    public function getActivityFeed(User $user, int $limit = 20): Collection
    {
        return Activity::query()
            ->with('user')
            ->where('visibility', 'public')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit($limit)
            ->get();
    }

    public function getFollowingFeed(User $user, int $limit = 20): Collection
    {
        return Activity::query()
            ->with('user')
            ->whereIn('user_id', $user->following()->pluck('following_id'))
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit($limit)
            ->get();
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function calculatePaceFromTime(float $distanceMeters, int $durationSeconds): string
    {
        if ($distanceMeters <= 0 || $durationSeconds <= 0) {
            return '0:00';
        }

        $paceSecondsPerKm = ($durationSeconds / $distanceMeters) * 1000;
        $minutes = floor($paceSecondsPerKm / 60);
        $seconds = round($paceSecondsPerKm % 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    private function formatPace(float $paceMinKm): string
    {
        $minutes = floor($paceMinKm);
        $seconds = round(($paceMinKm - $minutes) * 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    private function getEmptyStats(): array
    {
        return [
            'total_activities' => 0,
            'total_distance_meters' => 0,
            'total_distance_km' => 0,
            'total_duration_seconds' => 0,
            'total_duration_hours' => 0,
            'total_elevation_gain' => 0,
            'avg_distance_per_activity' => 0,
            'avg_duration_per_activity' => 0,
            'by_type' => [],
            'last_7_days' => [
                'count' => 0,
                'distance' => 0,
                'duration' => 0,
            ],
            'last_30_days' => [
                'count' => 0,
                'distance' => 0,
                'duration' => 0,
            ],
        ];
    }
}
