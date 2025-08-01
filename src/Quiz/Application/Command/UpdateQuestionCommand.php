<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to update an existing question.
 */
final class UpdateQuestionCommand implements CommandInterface
{
    public function __construct(
        public readonly int $questionId,
        public readonly ?string $content = null,
        public readonly ?array $answers = null,
        public readonly ?string $difficulty = null,
        public readonly ?array $tags = null,
        public readonly ?string $explanation = null,
        public readonly ?string $codeExample = null,
        public readonly ?int $timeLimit = null
    ) {
    }
}