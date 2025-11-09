<?php

declare(strict_types=1);

namespace App\Http\Resources\Segment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SegmentCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total() ?? $this->count(),
            ],
        ];
    }
}
