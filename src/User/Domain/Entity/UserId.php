<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a User identifier.
 * Extends the shared ID pattern specifically for User domain.
 */
final class UserId extends AbstractValueObject
{
    public function __construct(
        private readonly int $value
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        // For now, we'll use a simple approach. In production, you might want to use UUID or other generation strategies
        return new self(random_int(1, PHP_INT_MAX));
    }
}