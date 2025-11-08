<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use App\Models\Activity\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class ActivityTrackingService
{
    private const TRACKING_PREFIX = 'activity:tracking:';

    private const TRACKING_TTL = 7200;

    public function __construct() {}

    public function startActivity(User $user, ActivityType $type, string $title): string
    {
        $activityId = uniqid('act_', true);
        $redisKey = self::TRACKING_PREFIX.$activityId;

        $trackingData = [
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => $title,
            'status' => 'active',
            'started_at' => now()->toISOString(),
            'paused_at' => null,
            'total_pause_time' => 0,
            'points' => [],
        ];

        Redis::setex($redisKey, self::TRACKING_TTL, json_encode($trackingData));

        return $activityId;
    }

    public function trackLocation(string $activityId, float $latitude, float $longitude, ?float $altitude = null, ?int $heartRate = null): bool
    {
        $redisKey = self::TRACKING_PREFIX.$activityId;
        $data = $this->getTrackingData($activityId);

        if (! $data || $data['status'] !== 'active') {
            return false;
        }

        $point = [
            'lat' => $latitude,
            'lng' => $longitude,
            'alt' => $altitude,
            'hr' => $heartRate,
            'timestamp' => now()->toISOString(),
        ];

        $data['points'][] = $point;

        Redis::setex($redisKey, self::TRACKING_TTL, json_encode($data));

        return true;
    }

    public function pauseActivity(string $activityId): bool
    {
        $data = $this->getTrackingData($activityId);

        if (! $data || $data['status'] !== 'active') {
            return false;
        }

        $data['status'] = 'paused';
        $data['paused_at'] = now()->toISOString();

        $redisKey = self::TRACKING_PREFIX.$activityId;
        Redis::setex($redisKey, self::TRACKING_TTL, json_encode($data));

        return true;
    }

    public function resumeActivity(string $activityId): bool
    {
        $data = $this->getTrackingData($activityId);

        if (! $data || $data['status'] !== 'paused') {
            return false;
        }

        $pausedAt = new \DateTime($data['paused_at']);
        $pauseDuration = now()->diffInSeconds($pausedAt);

        $data['status'] = 'active';
        $data['total_pause_time'] += $pauseDuration;
        $data['paused_at'] = null;

        $redisKey = self::TRACKING_PREFIX.$activityId;
        Redis::setex($redisKey, self::TRACKING_TTL, json_encode($data));

        return true;
    }

    public function finishActivity(string $activityId, ?string $description = null, ?ActivityVisibility $visibility = null): ?Activity
    {
        $data = $this->getTrackingData($activityId);

        if (! $data) {
            return null;
        }

        $points = $data['points'];

        if (count($points) < 2) {
            return null;
        }

        $stats = $this->calculateStats($points, $data['started_at'], $data['total_pause_time']);

        $activity = Activity::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $description,
            'visibility' => $visibility?->value ?? ActivityVisibility::Public->value,
            'started_at' => $data['started_at'],
            'completed_at' => now(),
            'distance_meters' => $stats['distance_meters'],
            'duration_seconds' => $stats['duration_seconds'],
            'moving_time_seconds' => $stats['moving_time_seconds'],
            'elevation_gain' => $stats['elevation_gain'],
            'elevation_loss' => $stats['elevation_loss'],
            'avg_speed_kmh' => $stats['avg_speed_kmh'],
            'max_speed_kmh' => $stats['max_speed_kmh'],
            'avg_heart_rate' => $stats['avg_heart_rate'],
            'max_heart_rate' => $stats['max_heart_rate'],
            'raw_data' => ['points' => $points],
        ]);

        $redisKey = self::TRACKING_PREFIX.$activityId;
        Redis::del($redisKey);

        return $activity;
    }

    public function getTrackingData(string $activityId): ?array
    {
        $redisKey = self::TRACKING_PREFIX.$activityId;
        $data = Redis::get($redisKey);

        if (! $data) {
            return null;
        }

        return json_decode($data, true);
    }

    private function calculateStats(array $points, string $startedAt, int $totalPauseTime): array
    {
        $totalDistance = 0;
        $elevationGain = 0;
        $elevationLoss = 0;
        $maxSpeed = 0;
        $heartRates = [];

        for ($i = 1; $i < count($points); $i++) {
            $prevPoint = $points[$i - 1];
            $currPoint = $points[$i];

            $distance = $this->calculateDistance(
                $prevPoint['lat'],
                $prevPoint['lng'],
                $currPoint['lat'],
                $currPoint['lng']
            );

            $totalDistance += $distance;

            if ($prevPoint['alt'] !== null && $currPoint['alt'] !== null) {
                $elevationDiff = $currPoint['alt'] - $prevPoint['alt'];
                if ($elevationDiff > 0) {
                    $elevationGain += $elevationDiff;
                } else {
                    $elevationLoss += abs($elevationDiff);
                }
            }

            $timeDiff = (new \DateTime($currPoint['timestamp']))->getTimestamp() -
                       (new \DateTime($prevPoint['timestamp']))->getTimestamp();

            if ($timeDiff > 0) {
                $speed = ($distance / 1000) / ($timeDiff / 3600);
                $maxSpeed = max($maxSpeed, $speed);
            }

            if ($currPoint['hr'] !== null) {
                $heartRates[] = $currPoint['hr'];
            }
        }

        $startTime = new \DateTime($startedAt);
        $durationSeconds = now()->diffInSeconds($startTime);
        $movingTimeSeconds = $durationSeconds - $totalPauseTime;

        $avgSpeedKmh = $movingTimeSeconds > 0 ? ($totalDistance / 1000) / ($movingTimeSeconds / 3600) : 0;

        return [
            'distance_meters' => round($totalDistance, 2),
            'duration_seconds' => $durationSeconds,
            'moving_time_seconds' => $movingTimeSeconds,
            'elevation_gain' => round($elevationGain, 2),
            'elevation_loss' => round($elevationLoss, 2),
            'avg_speed_kmh' => round($avgSpeedKmh, 2),
            'max_speed_kmh' => round($maxSpeed, 2),
            'avg_heart_rate' => count($heartRates) > 0 ? (int) round(array_sum($heartRates) / count($heartRates)) : null,
            'max_heart_rate' => count($heartRates) > 0 ? max($heartRates) : null,
        ];
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
}
