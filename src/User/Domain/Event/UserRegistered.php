<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user registers in the system.
 */
final class UserRegistered extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
        private readonly string $username,
        private readonly string $role,
        \DateTimeImmutable $occurredAt
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getEventName(): string
    {
        return 'user.registered';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}