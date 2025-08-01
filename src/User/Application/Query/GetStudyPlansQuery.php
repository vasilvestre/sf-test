<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user study plans.
 */
final class GetStudyPlansQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly bool $includeCompleted = false,
        public readonly int $limit = 10,
        public readonly int $offset = 0
    ) {
    }
}