<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get all users with pagination.
 */
final class GetUsersQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?string $role = null
    ) {
    }
}