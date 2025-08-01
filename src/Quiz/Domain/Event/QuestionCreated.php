<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when a new question is created.
 */
final class QuestionCreated implements DomainEventInterface
{
    public function __construct(
        private readonly EnhancedQuestion $question,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getQuestion(): EnhancedQuestion
    {
        return $this->question;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAggregateId(): mixed
    {
        return $this->question->getId()?->toString();
    }

    public function getEventName(): string
    {
        return 'quiz.question.created';
    }

    public function getEventData(): array
    {
        return [
            'aggregate_id' => $this->getAggregateId(),
            'occurred_on' => $this->occurredOn->format(\DateTimeInterface::ATOM),
            'question_id' => $this->question->getId()?->toString(),
            'type' => $this->question->getType()->getValue(),
            'difficulty_level' => $this->question->getDifficultyLevel()->getLevel(),
            'scoring_weight' => $this->question->getScoringWeight(),
        ];
    }

    /**
     * @deprecated Use getEventData() instead
     */
    public function getPayload(): array
    {
        return [
            'questionId' => $this->question->getId()?->toString(),
            'type' => $this->question->getType()->getValue(),
            'difficultyLevel' => $this->question->getDifficultyLevel()->getLevel(),
            'scoringWeight' => $this->question->getScoringWeight(),
        ];
    }
}