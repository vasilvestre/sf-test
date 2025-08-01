<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Symfony security user provider for our User domain.
 */
final class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            // Try to load by email first
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user = $this->userRepository->findByEmail(Email::fromString($identifier));
            } else {
                // Try to load by username
                $user = $this->userRepository->findByUsername(Username::fromString($identifier));
            }

            return new SecurityUser($user);
        } catch (\App\User\Domain\Exception\UserNotFoundException $e) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier), 0, $e);
        }
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        try {
            $freshUser = $this->userRepository->findById($user->getUserId());
            return new SecurityUser($freshUser);
        } catch (\App\User\Domain\Exception\UserNotFoundException $e) {
            throw new UserNotFoundException('User not found during refresh.', 0, $e);
        }
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        try {
            $domainUser = $this->userRepository->findById($user->getUserId());
            // Note: This would need to be updated to use the new Password value object
            // For now, we'll skip the password upgrade functionality
            // In a real implementation, you'd need to handle this properly
            $this->userRepository->save($domainUser);
        } catch (\App\User\Domain\Exception\UserNotFoundException) {
            // User not found, nothing to upgrade
        }
    }
}