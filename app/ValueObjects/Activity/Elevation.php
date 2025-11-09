<?php

declare(strict_types=1);

namespace App\ValueObjects\Activity;

use Stringable;

final readonly class Elevation implements Stringable
{
    private function __construct(
        private float $meters
    ) {}

    public static function fromMeters(float $meters): self
    {
        return new self($meters);
    }

    public static function fromFeet(float $feet): self
    {
        return new self($feet * 0.3048);
    }

    public function toMeters(): float
    {
        return $this->meters;
    }

    public function toFeet(): float
    {
        return $this->meters / 0.3048;
    }

    public function add(self $other): self
    {
        return new self($this->meters + $other->meters);
    }

    public function subtract(self $other): self
    {
        return new self($this->meters - $other->meters);
    }

    public function isPositive(): bool
    {
        return $this->meters > 0;
    }

    public function isNegative(): bool
    {
        return $this->meters < 0;
    }

    public function abs(): self
    {
        return new self(abs($this->meters));
    }

    public function __toString(): string
    {
        return number_format($this->meters, 1).' m';
    }
}
