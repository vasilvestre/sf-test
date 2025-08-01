<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when difficulty is adjusted for a user.
 */
final class DifficultyAdjusted extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $categoryId,
        private readonly string $previousDifficulty,
        private readonly string $newDifficulty,
        private readonly string $reason,
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

    public function getPreviousDifficulty(): string
    {
        return $this->previousDifficulty;
    }

    public function getNewDifficulty(): string
    {
        return $this->newDifficulty;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getEventName(): string
    {
        return 'quiz.difficulty_adjusted';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'category_id' => $this->categoryId,
            'previous_difficulty' => $this->previousDifficulty,
            'new_difficulty' => $this->newDifficulty,
            'reason' => $this->reason,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}