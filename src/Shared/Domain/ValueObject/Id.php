<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Value object representing an identifier.
 */
final class Id extends AbstractValueObject
{
    public function __construct(
        private readonly int $value
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('ID must be a positive integer');
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
}