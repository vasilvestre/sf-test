<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Abstract base class for value objects.
 * Provides common functionality for immutable value objects.
 */
abstract class AbstractValueObject implements ValueObjectInterface
{
    public function equals(ValueObjectInterface $other): bool
    {
        if (!$other instanceof static) {
            return false;
        }

        return $this->toString() === $other->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}