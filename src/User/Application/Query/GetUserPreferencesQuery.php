<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user preferences.
 */
final class GetUserPreferencesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}