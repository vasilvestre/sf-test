<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to create a new quiz.
 */
final class CreateQuizCommand implements CommandInterface
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly int $categoryId,
        public readonly array $questionIds,
        public readonly ?int $timeLimit = null,
        public readonly string $difficulty = 'medium',
        public readonly bool $isPublished = false,
        public readonly array $tags = []
    ) {
    }
}