<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to submit quiz answers and calculate results.
 */
final class SubmitQuizCommand implements CommandInterface
{
    public function __construct(
        public readonly array $answers,
        public readonly ?int $categoryId = null,
        public readonly bool $isFailedQuestionsQuiz = false
    ) {
    }
}