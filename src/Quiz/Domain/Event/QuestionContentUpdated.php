<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when a question's content is updated.
 */
final class QuestionContentUpdated implements DomainEventInterface
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

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'quiz.question.content_updated';
    }

    public function getPayload(): array
    {
        return [
            'questionId' => $this->question->getId()?->toString(),
            'contentType' => $this->question->getContent()->getType(),
        ];
    }
}