<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user's profile is updated.
 */
final class UserProfileUpdated extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $updateType,
        \DateTimeImmutable $occurredAt
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUpdateType(): string
    {
        return $this->updateType;
    }

    public function getEventName(): string
    {
        return 'user.profile_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'update_type' => $this->updateType,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}