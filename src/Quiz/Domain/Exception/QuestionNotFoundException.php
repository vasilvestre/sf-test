<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when a question is not found.
 */
final class QuestionNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Question with ID "%d" was not found', $id));
    }
}