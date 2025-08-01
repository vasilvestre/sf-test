<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user achieves a personal best score.
 */
final class PersonalBestAchieved extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $categoryId,
        private readonly float $previousBest,
        private readonly float $newBest,
        \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getPreviousBest(): float
    {
        return $this->previousBest;
    }

    public function getNewBest(): float
    {
        return $this->newBest;
    }

    public function getEventName(): string
    {
        return 'quiz.personal_best_achieved';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'category_id' => $this->categoryId,
            'previous_best' => $this->previousBest,
            'new_best' => $this->newBest,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}