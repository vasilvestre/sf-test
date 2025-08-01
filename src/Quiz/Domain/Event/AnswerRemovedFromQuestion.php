<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedAnswer;
use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when an answer is removed from a question.
 */
final class AnswerRemovedFromQuestion implements DomainEventInterface
{
    public function __construct(
        private readonly EnhancedQuestion $question,
        private readonly EnhancedAnswer $answer,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getQuestion(): EnhancedQuestion
    {
        return $this->question;
    }

    public function getAnswer(): EnhancedAnswer
    {
        return $this->answer;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'quiz.question.answer_removed';
    }

    public function getPayload(): array
    {
        return [
            'questionId' => $this->question->getId()?->toString(),
            'answerId' => $this->answer->getId()?->toString(),
            'wasCorrect' => $this->answer->isCorrect(),
            'remainingAnswerCount' => count($this->question->getAnswers()),
        ];
    }
}