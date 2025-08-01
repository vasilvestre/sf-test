<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to verify a user's email address.
 */
final class VerifyUserEmailCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}