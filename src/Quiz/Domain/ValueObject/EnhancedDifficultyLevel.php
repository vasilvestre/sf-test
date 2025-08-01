<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Enhanced difficulty level supporting numeric scale and category context.
 * Provides more granular difficulty assessment for adaptive learning.
 */
final class EnhancedDifficultyLevel extends AbstractValueObject
{
    public const MIN_LEVEL = 1;
    public const MAX_LEVEL = 10;

    // Convenience constants for common difficulty levels
    public const BEGINNER = 1;
    public const EASY = 3;
    public const MEDIUM = 5;
    public const HARD = 7;
    public const EXPERT = 9;
    public const MASTER = 10;

    public function __construct(
        private readonly int $level,
        private readonly ?string $categoryContext = null
    ) {
        if ($level < self::MIN_LEVEL || $level > self::MAX_LEVEL) {
            throw new \InvalidArgumentException(
                sprintf('Difficulty level must be between %d and %d, got %d', self::MIN_LEVEL, self::MAX_LEVEL, $level)
            );
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getCategoryContext(): ?string
    {
        return $this->categoryContext;
    }

    public function toString(): string
    {
        $context = $this->categoryContext ? " ({$this->categoryContext})" : '';
        return "Level {$this->level}{$context}";
    }

    public function withCategoryContext(string $categoryContext): self
    {
        return new self($this->level, $categoryContext);
    }

    public function isBeginner(): bool
    {
        return $this->level <= 2;
    }

    public function isEasy(): bool
    {
        return $this->level >= 2 && $this->level <= 4;
    }

    public function isMedium(): bool
    {
        return $this->level >= 4 && $this->level <= 6;
    }

    public function isHard(): bool
    {
        return $this->level >= 6 && $this->level <= 8;
    }

    public function isExpert(): bool
    {
        return $this->level >= 8;
    }

    public function isEasierThan(self $other): bool
    {
        return $this->level < $other->level;
    }

    public function isHarderThan(self $other): bool
    {
        return $this->level > $other->level;
    }

    public function distanceFrom(self $other): int
    {
        return abs($this->level - $other->level);
    }

    public function adjustBy(int $adjustment): self
    {
        $newLevel = max(self::MIN_LEVEL, min(self::MAX_LEVEL, $this->level + $adjustment));
        return new self($newLevel, $this->categoryContext);
    }

    public function getPercentage(): float
    {
        return (($this->level - self::MIN_LEVEL) / (self::MAX_LEVEL - self::MIN_LEVEL)) * 100;
    }

    // Factory methods
    public static function beginner(?string $categoryContext = null): self
    {
        return new self(self::BEGINNER, $categoryContext);
    }

    public static function easy(?string $categoryContext = null): self
    {
        return new self(self::EASY, $categoryContext);
    }

    public static function medium(?string $categoryContext = null): self
    {
        return new self(self::MEDIUM, $categoryContext);
    }

    public static function hard(?string $categoryContext = null): self
    {
        return new self(self::HARD, $categoryContext);
    }

    public static function expert(?string $categoryContext = null): self
    {
        return new self(self::EXPERT, $categoryContext);
    }

    public static function master(?string $categoryContext = null): self
    {
        return new self(self::MASTER, $categoryContext);
    }

    public static function fromLevel(int $level, ?string $categoryContext = null): self
    {
        return new self($level, $categoryContext);
    }
}