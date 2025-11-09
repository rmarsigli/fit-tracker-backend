<?php declare(strict_types=1);

namespace App\Http\Resources\Activity;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'distance_meters' => $this->distance_meters,
            'distance_km' => $this->distance_meters ? round($this->distance_meters / 1000, 2) : null,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_seconds ? $this->formatDuration($this->duration_seconds) : null,
            'moving_time_seconds' => $this->moving_time_seconds,
            'elevation_gain' => $this->elevation_gain,
            'elevation_loss' => $this->elevation_loss,
            'avg_speed_kmh' => $this->avg_speed_kmh,
            'avg_pace_min_km' => $this->avg_speed_kmh ? $this->calculatePace($this->avg_speed_kmh) : null,
            'max_speed_kmh' => $this->max_speed_kmh,
            'avg_heart_rate' => $this->avg_heart_rate,
            'max_heart_rate' => $this->max_heart_rate,
            'calories' => $this->calories,
            'avg_cadence' => $this->avg_cadence,
            'splits' => $this->splits,
            'weather' => $this->weather,
            'visibility' => $this->visibility->value,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $secs);
        }

        return sprintf('%ds', $secs);
    }

    private function calculatePace(float $speedKmh): string
    {
        if ($speedKmh <= 0) {
            return '0:00';
        }

        $paceMinutesPerKm = 60 / $speedKmh;
        $minutes = floor($paceMinutesPerKm);
        $seconds = round(($paceMinutesPerKm - $minutes) * 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
