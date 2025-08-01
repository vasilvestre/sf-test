<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Value object representing a score as a percentage.
 */
final class Score extends AbstractValueObject
{
    public function __construct(
        private readonly float $value
    ) {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Score must be between 0 and 100');
        }
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function toString(): string
    {
        return number_format($this->value, 2) . '%';
    }

    public function isPassingScore(float $threshold = 60.0): bool
    {
        return $this->value >= $threshold;
    }

    public static function fromPercentage(float $percentage): self
    {
        return new self($percentage);
    }

    public static function fromFraction(int $correct, int $total): self
    {
        if ($total <= 0) {
            throw new \InvalidArgumentException('Total must be a positive integer');
        }

        if ($correct < 0 || $correct > $total) {
            throw new \InvalidArgumentException('Correct answers must be between 0 and total');
        }

        $percentage = ($correct / $total) * 100;
        return new self($percentage);
    }
}