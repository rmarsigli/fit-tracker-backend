<?php

declare(strict_types=1);

namespace App\ValueObjects\Common;

use InvalidArgumentException;
use Stringable;

final readonly class Duration implements Stringable
{
    private function __construct(
        private int $seconds
    ) {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Duration cannot be negative');
        }
    }

    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public static function fromMinutes(int $minutes): self
    {
        return new self($minutes * 60);
    }

    public static function fromHours(float $hours): self
    {
        return new self((int) round($hours * 3600));
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toMinutes(): float
    {
        return $this->seconds / 60;
    }

    public function toHours(): float
    {
        return $this->seconds / 3600;
    }

    public function add(self $other): self
    {
        return new self($this->seconds + $other->seconds);
    }

    public function subtract(self $other): self
    {
        return new self(max(0, $this->seconds - $other->seconds));
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->seconds > $other->seconds;
    }

    public function isLessThan(self $other): bool
    {
        return $this->seconds < $other->seconds;
    }

    public function equals(self $other): bool
    {
        return $this->seconds === $other->seconds;
    }

    public function format(): string
    {
        $hours = floor($this->seconds / 3600);
        $minutes = floor(($this->seconds % 3600) / 60);
        $seconds = $this->seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
