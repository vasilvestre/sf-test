<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get all available categories.
 */
final class GetCategoriesQuery implements QueryInterface
{
    public function __construct(
        public readonly bool $withQuestions = false
    ) {
    }
}