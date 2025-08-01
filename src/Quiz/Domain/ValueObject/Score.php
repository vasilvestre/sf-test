<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing complex scoring with breakdown and metadata.
 * Supports weighted scoring, partial credit, and performance analytics.
 */
final class Score extends AbstractValueObject
{
    public function __construct(
        private readonly float $points,
        private readonly float $maxPoints,
        private readonly float $percentage,
        private readonly array $breakdown = [],
        private readonly array $metadata = []
    ) {
        if ($points < 0) {
            throw new \InvalidArgumentException('Points cannot be negative');
        }

        if ($maxPoints <= 0) {
            throw new \InvalidArgumentException('Max points must be positive');
        }

        if ($points > $maxPoints) {
            throw new \InvalidArgumentException('Points cannot exceed max points');
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Percentage must be between 0 and 100');
        }

        // Verify percentage calculation
        $calculatedPercentage = round(($points / $maxPoints) * 100, 2);
        if (abs($percentage - $calculatedPercentage) > 0.01) {
            throw new \InvalidArgumentException('Percentage does not match points calculation');
        }
    }

    public function getPoints(): float
    {
        return $this->points;
    }

    public function getMaxPoints(): float
    {
        return $this->maxPoints;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function getBreakdown(): array
    {
        return $this->breakdown;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toString(): string
    {
        return sprintf('%.1f/%.1f (%.1f%%)', $this->points, $this->maxPoints, $this->percentage);
    }

    public function isPerfect(): bool
    {
        return $this->percentage >= 100.0;
    }

    public function isPassing(float $passingPercentage = 70.0): bool
    {
        return $this->percentage >= $passingPercentage;
    }

    public function getGrade(array $gradeScale = null): string
    {
        $gradeScale = $gradeScale ?? [
            90 => 'A',
            80 => 'B',
            70 => 'C',
            60 => 'D',
            0 => 'F'
        ];

        foreach ($gradeScale as $threshold => $grade) {
            if ($this->percentage >= $threshold) {
                return $grade;
            }
        }

        return 'F';
    }

    public function addToBreakdown(string $category, float $points, float $maxPoints): self
    {
        $breakdown = $this->breakdown;
        $breakdown[$category] = [
            'points' => $points,
            'maxPoints' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100, 2) : 0,
        ];

        return new self($this->points, $this->maxPoints, $this->percentage, $breakdown, $this->metadata);
    }

    public function withMetadata(array $metadata): self
    {
        return new self($this->points, $this->maxPoints, $this->percentage, $this->breakdown, array_merge($this->metadata, $metadata));
    }

    public function add(self $other): self
    {
        $newPoints = $this->points + $other->points;
        $newMaxPoints = $this->maxPoints + $other->maxPoints;
        $newPercentage = $newMaxPoints > 0 ? round(($newPoints / $newMaxPoints) * 100, 2) : 0;

        $newBreakdown = array_merge_recursive($this->breakdown, $other->breakdown);
        $newMetadata = array_merge($this->metadata, $other->metadata);

        return new self($newPoints, $newMaxPoints, $newPercentage, $newBreakdown, $newMetadata);
    }

    // Factory methods
    public static function create(float $points, float $maxPoints): self
    {
        $percentage = $maxPoints > 0 ? round(($points / $maxPoints) * 100, 2) : 0;
        return new self($points, $maxPoints, $percentage);
    }

    public static function perfect(float $maxPoints): self
    {
        return new self($maxPoints, $maxPoints, 100.0);
    }

    public static function zero(float $maxPoints): self
    {
        return new self(0.0, $maxPoints, 0.0);
    }

    public static function fromPercentage(float $percentage, float $maxPoints): self
    {
        $points = round(($percentage / 100) * $maxPoints, 2);
        return new self($points, $maxPoints, $percentage);
    }
}