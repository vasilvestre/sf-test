<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing a validated email address.
 */
final class Email extends AbstractValueObject
{
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

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    private function validate(string $email): void
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (strlen($email) > 320) {
            throw new \InvalidArgumentException('Email is too long (maximum 320 characters)');
        }

        // Additional validation for common issues
        if (substr_count($email, '@') !== 1) {
            throw new \InvalidArgumentException('Email must contain exactly one @ symbol');
        }
    }
}