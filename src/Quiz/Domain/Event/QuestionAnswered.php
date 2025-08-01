<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\ValueObject\Id;

/**
 * Domain event fired when a question is answered within a quiz session.
 * Enhanced version that supports comprehensive quiz session analytics.
 */
final class QuestionAnswered extends AbstractDomainEvent
{
    public function __construct(
        private readonly Id $sessionId,
        private readonly Id $questionId,
        private readonly array $submittedAnswers,
        private readonly bool $isCorrect,
        private readonly float $score,
        private readonly float $timeSpent,
        ?\DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($this->sessionId, $occurredOn);
    }

    public function getSessionId(): Id
    {
        return $this->sessionId;
    }

    public function getQuestionId(): Id
    {
        return $this->questionId;
    }

    public function getSubmittedAnswers(): array
    {
        return $this->submittedAnswers;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getTimeSpent(): float
    {
        return $this->timeSpent;
    }

    public function getEventName(): string
    {
        return 'quiz.question.answered';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'session_id' => $this->sessionId->toString(),
            'question_id' => $this->questionId->toString(),
            'submitted_answers' => $this->submittedAnswers,
            'is_correct' => $this->isCorrect,
            'score' => $this->score,
            'time_spent' => $this->timeSpent,
        ]);
    }

    /**
     * Get analytics payload for this event.
     */
    public function getAnalyticsPayload(): array
    {
        return [
            'event_type' => 'question_answered',
            'session_id' => $this->sessionId->toString(),
            'question_id' => $this->questionId->toString(),
            'answer_data' => [
                'submitted_answers' => $this->submittedAnswers,
                'is_correct' => $this->isCorrect,
                'score' => $this->score,
                'time_spent' => $this->timeSpent,
            ],
            'timestamp' => $this->getOccurredOn()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Legacy method for backward compatibility.
     * @deprecated Use getEventData() instead
     */
    public function toArray(): array
    {
        return $this->getEventData();
    }
}