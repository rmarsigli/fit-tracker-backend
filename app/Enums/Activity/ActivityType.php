<?php declare(strict_types=1);

namespace App\Enums\Activity;

enum ActivityType: string
{
    case Run = 'run';
    case Ride = 'ride';
    case Walk = 'walk';
    case Swim = 'swim';
    case Gym = 'gym';
    case Other = 'other';
}
