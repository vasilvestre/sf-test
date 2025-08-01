<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to enable two-factor authentication for a user.
 */
final class EnableTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $method = 'email' // email, sms, app
    ) {
    }
}