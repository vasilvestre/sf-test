<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedQuiz;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when a quiz is published.
 */
final class QuizPublished implements DomainEventInterface
{
    public function __construct(
        private readonly EnhancedQuiz $quiz,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getQuiz(): EnhancedQuiz
    {
        return $this->quiz;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'quiz.quiz.published';
    }

    public function getPayload(): array
    {
        return [
            'quizId' => $this->quiz->getId()?->toString(),
            'title' => $this->quiz->getTitle(),
            'questionCount' => $this->quiz->getQuestionCount(),
            'publishedAt' => $this->quiz->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}