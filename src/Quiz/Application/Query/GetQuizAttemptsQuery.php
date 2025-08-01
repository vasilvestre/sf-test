<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get quiz attempts for a user.
 */
final class GetQuizAttemptsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $quizId = null,
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?string $orderBy = 'completed_at',
        public readonly string $orderDirection = 'DESC'
    ) {
    }
}