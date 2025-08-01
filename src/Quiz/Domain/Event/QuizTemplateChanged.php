<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedQuiz;
use App\Quiz\Domain\ValueObject\QuizTemplate;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when a quiz template changes.
 */
final class QuizTemplateChanged implements DomainEventInterface
{
    public function __construct(
        private readonly EnhancedQuiz $quiz,
        private readonly QuizTemplate $newTemplate,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getQuiz(): EnhancedQuiz
    {
        return $this->quiz;
    }

    public function getNewTemplate(): QuizTemplate
    {
        return $this->newTemplate;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'quiz.quiz.template_changed';
    }

    public function getPayload(): array
    {
        return [
            'quizId' => $this->quiz->getId()?->toString(),
            'newTemplate' => $this->newTemplate->getMode(),
            'templateConfig' => $this->newTemplate->getConfiguration(),
        ];
    }
}