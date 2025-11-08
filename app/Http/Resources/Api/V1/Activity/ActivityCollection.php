<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Activity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityCollection extends ResourceCollection
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
