<?php declare(strict_types=1);

namespace App\ValueObjects\Activity;

use App\ValueObjects\Common\Distance;
use App\ValueObjects\Common\Duration;
use InvalidArgumentException;
use Stringable;

final readonly class Pace implements Stringable
{
    private function __construct(
        private int $secondsPerKm
    ) {
        if ($secondsPerKm < 0) {
            throw new InvalidArgumentException('Pace cannot be negative');
        }
    }

    public static function fromSecondsPerKm(int $secondsPerKm): self
    {
        return new self($secondsPerKm);
    }

    public static function fromDistanceAndDuration(Distance $distance, Duration $duration): self
    {
        if ($distance->toKilometers() <= 0) {
            throw new InvalidArgumentException('Distance must be greater than zero');
        }

        $secondsPerKm = (int) round($duration->toSeconds() / $distance->toKilometers());

        return new self($secondsPerKm);
    }

    public static function fromSpeed(Speed $speed): self
    {
        if ($speed->toKmh() <= 0) {
            throw new InvalidArgumentException('Speed must be greater than zero');
        }

        $secondsPerKm = (int) round(3600 / $speed->toKmh());

        return new self($secondsPerKm);
    }

    public function toSecondsPerKm(): int
    {
        return $this->secondsPerKm;
    }

    public function toSecondsPerMile(): int
    {
        return (int) round($this->secondsPerKm * 1.60934);
    }

    public function toSpeed(): Speed
    {
        if ($this->secondsPerKm === 0) {
            return Speed::fromKmh(0);
        }

        return Speed::fromKmh(3600 / $this->secondsPerKm);
    }

    public function format(): string
    {
        $minutes = floor($this->secondsPerKm / 60);
        $seconds = $this->secondsPerKm % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function formatPerMile(): string
    {
        $secondsPerMile = $this->toSecondsPerMile();
        $minutes = floor($secondsPerMile / 60);
        $seconds = $secondsPerMile % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function isFasterThan(self $other): bool
    {
        return $this->secondsPerKm < $other->secondsPerKm;
    }

    public function isSlowerThan(self $other): bool
    {
        return $this->secondsPerKm > $other->secondsPerKm;
    }

    public function equals(self $other): bool
    {
        return $this->secondsPerKm === $other->secondsPerKm;
    }

    public function __toString(): string
    {
        return $this->format().' /km';
    }
}
