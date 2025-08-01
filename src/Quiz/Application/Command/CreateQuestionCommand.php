<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to create a new question.
 */
final class CreateQuestionCommand implements CommandInterface
{
    public function __construct(
        public readonly string $content,
        public readonly string $type, // multiple_choice, single_choice, true_false, essay, code_completion
        public readonly array $answers,
        public readonly int $categoryId,
        public readonly string $difficulty = 'medium',
        public readonly array $tags = [],
        public readonly ?string $explanation = null,
        public readonly ?string $codeExample = null,
        public readonly ?int $timeLimit = null
    ) {
    }
}