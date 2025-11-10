<?php

declare(strict_types=1);

namespace App\Enums\Challenge;

enum ChallengeType: string
{
    case Distance = 'distance';
    case Duration = 'duration';
    case Elevation = 'elevation';

    public function label(): string
    {
        return match ($this) {
            self::Distance => 'Distance Challenge',
            self::Duration => 'Duration Challenge',
            self::Elevation => 'Elevation Challenge',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Distance => 'km',
            self::Duration => 'hours',
            self::Elevation => 'meters',
        };
    }
}
