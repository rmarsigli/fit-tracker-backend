<?php

declare(strict_types=1);

namespace App\ValueObjects\Geo;

use InvalidArgumentException;
use Stringable;

final readonly class Coordinates implements Stringable
{
    private function __construct(
        private float $latitude,
        private float $longitude,
        private ?float $altitude = null
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180');
        }
    }

    public static function from(float $latitude, float $longitude, ?float $altitude = null): self
    {
        return new self($latitude, $longitude, $altitude);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['latitude'] ?? $data['lat'],
            $data['longitude'] ?? $data['lng'] ?? $data['lon'],
            $data['altitude'] ?? $data['alt'] ?? null
        );
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function altitude(): ?float
    {
        return $this->altitude;
    }

    public function hasAltitude(): bool
    {
        return $this->altitude !== null;
    }

    public function toArray(): array
    {
        $array = [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if ($this->altitude !== null) {
            $array['altitude'] = $this->altitude;
        }

        return $array;
    }

    public function toWkt(): string
    {
        if ($this->altitude !== null) {
            return sprintf('POINT Z(%f %f %f)', $this->longitude, $this->latitude, $this->altitude);
        }

        return sprintf('POINT(%f %f)', $this->longitude, $this->latitude);
    }

    public function distanceTo(self $other): float
    {
        $earthRadius = 6371; // km

        $lat1 = deg2rad($this->latitude);
        $lon1 = deg2rad($this->longitude);
        $lat2 = deg2rad($other->latitude);
        $lon2 = deg2rad($other->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) ** 2 +
            cos($lat1) * cos($lat2) *
            sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function equals(self $other): bool
    {
        return abs($this->latitude - $other->latitude) < 0.0001
            && abs($this->longitude - $other->longitude) < 0.0001;
    }

    public function __toString(): string
    {
        return sprintf('(%.6f, %.6f)', $this->latitude, $this->longitude);
    }
}
