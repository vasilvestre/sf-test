<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to register a new user.
 */
final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $plainPassword,
        public readonly string $role = 'ROLE_STUDENT'
    ) {
    }
}