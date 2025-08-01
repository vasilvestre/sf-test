<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get a user by their ID.
 */
final class GetUserByIdQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}