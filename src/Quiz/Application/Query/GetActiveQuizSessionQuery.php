<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user's current active quiz session.
 * Returns the session that is currently in progress for the user.
 */
final class GetActiveQuizSessionQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly bool $includeQuestions = true,
        public readonly bool $includeProgress = true,
        public readonly bool $includeAdaptiveData = false
    ) {
    }
}