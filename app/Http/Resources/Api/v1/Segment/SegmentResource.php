<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\v1\Segment;

use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SegmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'distance_meters' => $this->distance_meters,
            'distance_km' => round($this->distance_meters / 1000, 2),
            'avg_grade_percent' => $this->avg_grade_percent,
            'max_grade_percent' => $this->max_grade_percent,
            'elevation_gain' => $this->elevation_gain,
            'total_attempts' => $this->total_attempts,
            'unique_athletes' => $this->unique_athletes,
            'city' => $this->city,
            'state' => $this->state,
            'is_hazardous' => $this->is_hazardous,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
