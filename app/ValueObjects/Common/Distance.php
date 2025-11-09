<?php

declare(strict_types=1);

namespace App\ValueObjects\Common;

use InvalidArgumentException;
use Stringable;

final readonly class Distance implements Stringable
{
    private function __construct(
        private float $meters
    ) {
        if ($meters < 0) {
            throw new InvalidArgumentException('Distance cannot be negative');
        }
    }

    public static function fromMeters(float $meters): self
    {
        return new self($meters);
    }

    public static function fromKilometers(float $kilometers): self
    {
        return new self($kilometers * 1000);
    }

    public static function fromMiles(float $miles): self
    {
        return new self($miles * 1609.34);
    }

    public function toMeters(): float
    {
        return $this->meters;
    }

    public function toKilometers(): float
    {
        return $this->meters / 1000;
    }

    public function toMiles(): float
    {
        return $this->meters / 1609.34;
    }

    public function add(self $other): self
    {
        return new self($this->meters + $other->meters);
    }

    public function subtract(self $other): self
    {
        return new self(max(0, $this->meters - $other->meters));
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->meters > $other->meters;
    }

    public function isLessThan(self $other): bool
    {
        return $this->meters < $other->meters;
    }

    public function equals(self $other): bool
    {
        return abs($this->meters - $other->meters) < 0.01;
    }

    public function __toString(): string
    {
        if ($this->meters >= 1000) {
            return number_format($this->toKilometers(), 2).' km';
        }

        return number_format($this->meters, 0).' m';
    }
}
