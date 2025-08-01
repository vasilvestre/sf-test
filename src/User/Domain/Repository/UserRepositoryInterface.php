<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\Shared\Domain\Repository\RepositoryInterface;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;

/**
 * Repository interface for User aggregate.
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Save a user to the repository.
     */
    public function save(User $user): void;

    /**
     * Find a user by their ID.
     *
     * @throws \App\User\Domain\Exception\UserNotFoundException
     */
    public function findById(Id $id): User;

    /**
     * Find a user by their email address.
     *
     * @throws \App\User\Domain\Exception\UserNotFoundException
     */
    public function findByEmail(Email $email): User;

    /**
     * Find a user by their username.
     *
     * @throws \App\User\Domain\Exception\UserNotFoundException
     */
    public function findByUsername(Username $username): User;

    /**
     * Check if a user exists with the given email.
     */
    public function existsByEmail(Email $email): bool;

    /**
     * Check if a user exists with the given username.
     */
    public function existsByUsername(Username $username): bool;

    /**
     * Find all users with pagination.
     *
     * @return User[]
     */
    public function findAll(int $page = 1, int $limit = 20): array;

    /**
     * Count total number of users.
     */
    public function count(): int;

    /**
     * Remove a user from the repository.
     */
    public function remove(User $user): void;

    /**
     * Find users by role.
     *
     * @return User[]
     */
    public function findByRole(string $role, int $page = 1, int $limit = 20): array;

    /**
     * Find unverified users older than the given date.
     *
     * @return User[]
     */
    public function findUnverifiedOlderThan(\DateTimeImmutable $date): array;
}