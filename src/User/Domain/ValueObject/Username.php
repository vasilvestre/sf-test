<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a unique username.
 */
final class Username extends AbstractValueObject
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 30;
    private const VALID_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    public function __construct(
        private readonly string $value
    ) {
        $this->validate($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $username): self
    {
        return new self($username);
    }

    private function validate(string $username): void
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        if (strlen($username) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Username must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if (strlen($username) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Username cannot be longer than %d characters', self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::VALID_PATTERN, $username)) {
            throw new \InvalidArgumentException(
                'Username can only contain letters, numbers, underscores, and hyphens'
            );
        }

        // Prevent reserved usernames
        $reservedUsernames = ['admin', 'root', 'administrator', 'moderator', 'system', 'api'];
        if (in_array(strtolower($username), $reservedUsernames, true)) {
            throw new \InvalidArgumentException('This username is reserved');
        }
    }
}