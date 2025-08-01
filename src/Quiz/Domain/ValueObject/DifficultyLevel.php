<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a question difficulty level.
 */
final class DifficultyLevel extends AbstractValueObject
{
    public const EASY = 'easy';
    public const MEDIUM = 'medium';
    public const HARD = 'hard';

    private const VALID_LEVELS = [self::EASY, self::MEDIUM, self::HARD];

    public function __construct(
        private readonly string $value
    ) {
        if (!in_array($value, self::VALID_LEVELS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid difficulty level "%s". Valid levels are: %s', $value, implode(', ', self::VALID_LEVELS))
            );
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isEasy(): bool
    {
        return $this->value === self::EASY;
    }

    public function isMedium(): bool
    {
        return $this->value === self::MEDIUM;
    }

    public function isHard(): bool
    {
        return $this->value === self::HARD;
    }

    public static function easy(): self
    {
        return new self(self::EASY);
    }

    public static function medium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function hard(): self
    {
        return new self(self::HARD);
    }
}