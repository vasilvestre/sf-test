<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user earns an achievement.
 */
final class AchievementEarned extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly string $achievementType,
        private readonly array $metadata,
        \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAchievementType(): string
    {
        return $this->achievementType;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getEventName(): string
    {
        return 'user.achievement_earned';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'achievement_type' => $this->achievementType,
            'metadata' => $this->metadata,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}