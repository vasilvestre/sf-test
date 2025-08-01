<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to update a user's preferences.
 */
final class UpdateUserPreferencesCommand implements CommandInterface
{
    /**
     * @param array<string, mixed> $preferences
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $preferences
    ) {
    }
}