<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Domain event fired when a question's difficulty level changes.
 */
final class QuestionDifficultyChanged implements DomainEventInterface
{
    public function __construct(
        private readonly EnhancedQuestion $question,
        private readonly EnhancedDifficultyLevel $oldLevel,
        private readonly EnhancedDifficultyLevel $newLevel,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getQuestion(): EnhancedQuestion
    {
        return $this->question;
    }

    public function getOldLevel(): EnhancedDifficultyLevel
    {
        return $this->oldLevel;
    }

    public function getNewLevel(): EnhancedDifficultyLevel
    {
        return $this->newLevel;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventName(): string
    {
        return 'quiz.question.difficulty_changed';
    }

    public function getPayload(): array
    {
        return [
            'questionId' => $this->question->getId()?->toString(),
            'oldLevel' => $this->oldLevel->getLevel(),
            'newLevel' => $this->newLevel->getLevel(),
            'levelChange' => $this->newLevel->getLevel() - $this->oldLevel->getLevel(),
        ];
    }
}