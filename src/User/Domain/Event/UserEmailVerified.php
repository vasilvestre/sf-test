<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user's email is verified.
 */
final class UserEmailVerified extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $email,
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

    public function getEventName(): string
    {
        return 'user.email_verified';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}