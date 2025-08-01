<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get questions for a quiz.
 */
final class GetQuizQuestionsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly int $limit = 15,
        public readonly bool $randomOrder = true
    ) {
    }
}