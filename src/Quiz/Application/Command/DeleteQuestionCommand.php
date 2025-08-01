<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to delete a question.
 */
final class DeleteQuestionCommand implements CommandInterface
{
    public function __construct(
        public readonly int $questionId,
        public readonly int $deletedBy,
        public readonly string $reason = 'Manual deletion'
    ) {
    }
}