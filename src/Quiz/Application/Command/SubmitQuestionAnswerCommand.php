<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to submit an answer for a question in an active quiz session.
 * Supports multiple question types and partial credit scoring.
 */
final readonly class SubmitQuestionAnswerCommand implements CommandInterface
{
    public function __construct(
        public string $sessionId,
        public string $questionId,
        public array $answers,
        public float $timeSpent,
        public ?array $metadata = null
    ) {
    }
}