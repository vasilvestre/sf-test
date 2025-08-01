<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user achievements.
 */
final class GetUserAchievementsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?string $type = null
    ) {
    }
}