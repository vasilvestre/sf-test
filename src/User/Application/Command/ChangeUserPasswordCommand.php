<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to change a user's password.
 */
final class ChangeUserPasswordCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currentPassword,
        public readonly string $newPassword
    ) {
    }
}