<?php declare(strict_types=1);

namespace App\ValueObjects\Activity;

use InvalidArgumentException;
use Stringable;

final readonly class HeartRate implements Stringable
{
    private const MIN_BPM = 30;

    private const MAX_BPM = 220;

    private function __construct(
        private int $bpm
    ) {
        if ($bpm < self::MIN_BPM || $bpm > self::MAX_BPM) {
            throw new InvalidArgumentException(
                sprintf('Heart rate must be between %d and %d bpm', self::MIN_BPM, self::MAX_BPM)
            );
        }
    }

    public static function fromBpm(int $bpm): self
    {
        return new self($bpm);
    }

    public function toBpm(): int
    {
        return $this->bpm;
    }

    public function getZone(int $maxHeartRate): int
    {
        $percentage = ($this->bpm / $maxHeartRate) * 100;

        return match (true) {
            $percentage >= 90 => 5, // Maximum
            $percentage >= 80 => 4, // Hard
            $percentage >= 70 => 3, // Moderate
            $percentage >= 60 => 2, // Light
            default => 1, // Very light
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->bpm > $other->bpm;
    }

    public function isLowerThan(self $other): bool
    {
        return $this->bpm < $other->bpm;
    }

    public function equals(self $other): bool
    {
        return $this->bpm === $other->bpm;
    }

    public function __toString(): string
    {
        return $this->bpm.' bpm';
    }
}
