<?php

declare(strict_types=1);

namespace App\Enums\Segment;

enum SegmentType: string
{
    case Run = 'run';
    case Ride = 'ride';
}
