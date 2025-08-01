<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to submit a quiz attempt with answers.
 */
final class SubmitQuizAttemptCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly int $quizId,
        public readonly array $answers, // questionId => answerId/answerText
        public readonly int $timeSpent, // in seconds
        public readonly \DateTimeImmutable $startedAt,
        public readonly \DateTimeImmutable $completedAt = new \DateTimeImmutable()
    ) {
    }
}