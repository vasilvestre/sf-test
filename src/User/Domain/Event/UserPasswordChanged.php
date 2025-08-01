<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user's password is changed.
 */
final class UserPasswordChanged extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        \DateTimeImmutable $occurredAt
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEventName(): string
    {
        return 'user.password_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}