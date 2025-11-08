<?php declare(strict_types=1);

namespace App\ValueObjects\Activity;

use InvalidArgumentException;
use Stringable;

final readonly class Speed implements Stringable
{
    private function __construct(
        private float $kmh
    ) {
        if ($kmh < 0) {
            throw new InvalidArgumentException('Speed cannot be negative');
        }
    }

    public static function fromKmh(float $kmh): self
    {
        return new self($kmh);
    }

    public static function fromMs(float $ms): self
    {
        return new self($ms * 3.6);
    }

    public static function fromMph(float $mph): self
    {
        return new self($mph * 1.60934);
    }

    public function toKmh(): float
    {
        return $this->kmh;
    }

    public function toMs(): float
    {
        return $this->kmh / 3.6;
    }

    public function toMph(): float
    {
        return $this->kmh / 1.60934;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->kmh > $other->kmh;
    }

    public function isLessThan(self $other): bool
    {
        return $this->kmh < $other->kmh;
    }

    public function equals(self $other): bool
    {
        return abs($this->kmh - $other->kmh) < 0.01;
    }

    public function __toString(): string
    {
        return number_format($this->kmh, 2).' km/h';
    }
}
