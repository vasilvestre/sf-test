<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a quiz category name.
 */
final class CategoryName extends AbstractValueObject
{
    public function __construct(
        private readonly string $value
    ) {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }

        if (mb_strlen($value) > 255) {
            throw new \InvalidArgumentException('Category name cannot exceed 255 characters');
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

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}