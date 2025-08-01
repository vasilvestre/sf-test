<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when invalid quiz data is provided.
 */
final class InvalidQuizDataException extends DomainException
{
    public static function emptyQuestions(): self
    {
        return new self('Quiz must contain at least one question');
    }

    public static function invalidAnswerCount(): self
    {
        return new self('Each question must have at least 2 answers with exactly one correct answer');
    }

    public static function noCorrectAnswer(): self
    {
        return new self('Each question must have at least one correct answer');
    }
}