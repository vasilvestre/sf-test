<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a quiz attempt is completed.
 */
final class QuizAttemptCompleted extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $quizId,
        private readonly float $score,
        private readonly int $duration,
        private readonly int $correctAnswers,
        private readonly int $totalQuestions,
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

    public function getScore(): float
    {
        return $this->score;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getCorrectAnswers(): int
    {
        return $this->correctAnswers;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function getEventName(): string
    {
        return 'quiz.attempt_completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'quiz_id' => $this->quizId,
            'score' => $this->score,
            'duration' => $this->duration,
            'correct_answers' => $this->correctAnswers,
            'total_questions' => $this->totalQuestions,
            'occurred_at' => $this->getOccurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}