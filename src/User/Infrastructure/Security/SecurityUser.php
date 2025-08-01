<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Security user wrapper for Symfony Security component.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly User $user
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail()->getValue();
    }

    public function getUsername(): string
    {
        return $this->user->getUsername()->getValue();
    }

    public function getRoles(): array
    {
        return [$this->user->getRole()->getValue()];
    }

    public function getPassword(): string
    {
        return $this->user->getPassword()->getValue();
    }

    public function getSalt(): ?string
    {
        return null; // Not needed when using modern hashing algorithms
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase since we don't store plain passwords
    }

    public function getUserId(): Id
    {
        return $this->user->getId();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isEmailVerified(): bool
    {
        return $this->user->isEmailVerified();
    }

    public function __call(string $method, array $arguments): mixed
    {
        // Delegate all other method calls to the underlying User entity
        return $this->user->$method(...$arguments);
    }
}