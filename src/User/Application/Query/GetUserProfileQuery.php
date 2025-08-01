<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get user profile information.
 */
final class GetUserProfileQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly bool $includeAchievements = false,
        public readonly bool $includePreferences = false
    ) {
    }
}