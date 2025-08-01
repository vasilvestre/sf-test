<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a hashed password with strength validation.
 */
final class Password extends AbstractValueObject
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 128;

    public function __construct(
        private readonly string $hashedValue
    ) {
        if (empty($hashedValue)) {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }
    }

    public function getValue(): string
    {
        return $this->hashedValue;
    }

    public function toString(): string
    {
        return $this->hashedValue;
    }

    public static function fromPlainText(string $plainPassword): self
    {
        self::validatePlainPassword($plainPassword);
        
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return new self($hashedPassword);
    }

    public static function fromHash(string $hashedPassword): self
    {
        return new self($hashedPassword);
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_DEFAULT);
    }

    private static function validatePlainPassword(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if (strlen($password) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password cannot be longer than %d characters', self::MAX_LENGTH)
            );
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one uppercase letter');
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one lowercase letter');
        }

        // Check for at least one digit
        if (!preg_match('/\d/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one digit');
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one special character');
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '123456789', 'qwerty123',
            'admin123', 'letmein123', 'welcome123'
        ];
        
        if (in_array(strtolower($password), $weakPasswords, true)) {
            throw new \InvalidArgumentException('This password is too common and weak');
        }
    }
}