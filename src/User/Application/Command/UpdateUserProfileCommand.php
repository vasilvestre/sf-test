<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to update a user's profile.
 */
final class UpdateUserProfileCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $bio = null,
        public readonly ?\DateTimeImmutable $dateOfBirth = null
    ) {
    }
}