<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get recommended questions for a user.
 */
final class GetRecommendedQuestionsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $categoryId = null,
        public readonly int $limit = 10,
        public readonly string $algorithm = 'adaptive' // adaptive, random, difficulty_based
    ) {
    }
}