<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Interface for value objects in the domain layer.
 * Value objects are immutable and compared by value, not identity.
 */
interface ValueObjectInterface
{
    /**
     * Check if this value object is equal to another.
     */
    public function equals(ValueObjectInterface $other): bool;

    /**
     * Return a string representation of this value object.
     */
    public function toString(): string;
}