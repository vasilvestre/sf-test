<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a quiz attempt is started.
 */
final class QuizAttemptStarted extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $quizId,
        private readonly \DateTimeImmutable $startTime,
        \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getQuizId(): int
    {
        return $this->quizId;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEventName(): string
    {
        return 'quiz.attempt_started';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'quiz_id' => $this->quizId,
            'start_time' => $this->startTime->format(\DateTimeInterface::ATOM),
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}