<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;

/**
 * Domain event fired when a quiz session is started.
 * Captures the initial state and configuration of a quiz session.
 */
final class QuizSessionStarted extends AbstractDomainEvent
{
    public function __construct(
        private readonly Id $sessionId,
        private readonly UserId $userId,
        private readonly int $totalQuestions,
        private readonly EnhancedDifficultyLevel $targetDifficulty,
        private readonly bool $adaptiveLearning,
        private readonly bool $practiceMode,
        ?\DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($this->sessionId, $occurredOn);
    }

    public function getSessionId(): Id
    {
        return $this->sessionId;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function getTargetDifficulty(): EnhancedDifficultyLevel
    {
        return $this->targetDifficulty;
    }

    public function isAdaptiveLearning(): bool
    {
        return $this->adaptiveLearning;
    }

    public function isPracticeMode(): bool
    {
        return $this->practiceMode;
    }

    public function getEventName(): string
    {
        return 'quiz.session.started';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'session_id' => $this->sessionId->toString(),
            'user_id' => $this->userId->toString(),
            'total_questions' => $this->totalQuestions,
            'target_difficulty' => $this->targetDifficulty->getLevel(),
            'adaptive_learning' => $this->adaptiveLearning,
            'practice_mode' => $this->practiceMode,
        ]);
    }

    /**
     * Get analytics payload for this event.
     */
    public function getAnalyticsPayload(): array
    {
        return [
            'event_type' => 'quiz_session_started',
            'session_id' => $this->sessionId->toString(),
            'user_id' => $this->userId->toString(),
            'configuration' => [
                'total_questions' => $this->totalQuestions,
                'target_difficulty' => $this->targetDifficulty->getLevel(),
                'adaptive_learning_enabled' => $this->adaptiveLearning,
                'practice_mode' => $this->practiceMode,
            ],
            'timestamp' => $this->getOccurredOn()->format(\DateTimeInterface::ATOM),
        ];
    }
}