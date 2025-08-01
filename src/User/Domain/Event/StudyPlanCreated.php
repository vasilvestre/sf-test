<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a user creates a study plan.
 */
final class StudyPlanCreated extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $planId,
        private readonly string $planName,
        private readonly \DateTimeImmutable $targetDate,
        \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPlanId(): int
    {
        return $this->planId;
    }

    public function getPlanName(): string
    {
        return $this->planName;
    }

    public function getTargetDate(): \DateTimeImmutable
    {
        return $this->targetDate;
    }

    public function getEventName(): string
    {
        return 'user.study_plan_created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'plan_id' => $this->planId,
            'plan_name' => $this->planName,
            'target_date' => $this->targetDate->format(\DateTimeInterface::ATOM),
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}