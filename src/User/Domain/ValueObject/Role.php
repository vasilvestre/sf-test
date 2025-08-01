<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing user roles with hierarchy.
 */
final class Role extends AbstractValueObject
{
    public const STUDENT = 'ROLE_STUDENT';
    public const INSTRUCTOR = 'ROLE_INSTRUCTOR';
    public const ADMIN = 'ROLE_ADMIN';
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    private const VALID_ROLES = [
        self::STUDENT,
        self::INSTRUCTOR,
        self::ADMIN,
        self::SUPER_ADMIN,
    ];

    private const ROLE_HIERARCHY = [
        self::SUPER_ADMIN => [self::ADMIN, self::INSTRUCTOR, self::STUDENT],
        self::ADMIN => [self::INSTRUCTOR, self::STUDENT],
        self::INSTRUCTOR => [self::STUDENT],
        self::STUDENT => [],
    ];

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

    public static function student(): self
    {
        return new self(self::STUDENT);
    }

    public static function instructor(): self
    {
        return new self(self::INSTRUCTOR);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function superAdmin(): self
    {
        return new self(self::SUPER_ADMIN);
    }

    public static function fromString(string $role): self
    {
        return new self($role);
    }

    public function hasPermission(Role $requiredRole): bool
    {
        if ($this->value === $requiredRole->getValue()) {
            return true;
        }

        return in_array($requiredRole->getValue(), self::ROLE_HIERARCHY[$this->value] ?? [], true);
    }

    public function isStudent(): bool
    {
        return $this->value === self::STUDENT;
    }

    public function isInstructor(): bool
    {
        return $this->value === self::INSTRUCTOR;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->value === self::SUPER_ADMIN;
    }

    /**
     * @return string[]
     */
    public static function getAllRoles(): array
    {
        return self::VALID_ROLES;
    }

    private function validate(string $role): void
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid role "%s". Valid roles are: %s', $role, implode(', ', self::VALID_ROLES))
            );
        }
    }
}