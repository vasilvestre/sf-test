<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get questions by tags.
 */
final class GetQuestionsByTagQuery implements QueryInterface
{
    public function __construct(
        public readonly array $tags,
        public readonly ?string $difficulty = null,
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?int $categoryId = null
    ) {
    }
}