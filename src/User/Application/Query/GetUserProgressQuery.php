<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user progress in specific category.
 */
final class GetUserProgressQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $categoryId = null,
        public readonly ?string $period = null // daily, weekly, monthly
    ) {
    }
}