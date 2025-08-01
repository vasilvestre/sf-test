<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Exception;

/**
 * Exception thrown when a quiz session is not found.
 */
final class QuizSessionNotFoundException extends \DomainException
{
    public static function withId(string $sessionId): self
    {
        return new self("Quiz session with ID '{$sessionId}' was not found");
    }

    public static function activeForUser(int $userId): self
    {
        return new self("No active quiz session found for user ID '{$userId}'");
    }
}