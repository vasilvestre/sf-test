<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when a user is not found.
 */
final class UserNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('User with ID %d not found', $id));
    }

    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" not found', $email));
    }

    public static function withUsername(string $username): self
    {
        return new self(sprintf('User with username "%s" not found', $username));
    }
}