<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when a category is not found.
 */
final class CategoryNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Category with ID "%d" was not found', $id));
    }

    public static function withName(string $name): self
    {
        return new self(sprintf('Category with name "%s" was not found', $name));
    }
}