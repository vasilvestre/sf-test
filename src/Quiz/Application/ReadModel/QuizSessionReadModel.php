<?php

declare(strict_types=1);

namespace App\Quiz\Application\ReadModel;

/**
 * Read model for quiz session data transfer.
 * Optimized for displaying quiz session information in the UI.
 */
final class QuizSessionReadModel
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly array $questions,
        public readonly int $currentQuestionIndex,
        public readonly int $totalQuestions,
        public readonly float $progress,
        public readonly bool $isCompleted,
        public readonly bool $isPracticeMode,
        public readonly string $targetDifficulty,
        public readonly ?\DateTimeImmutable $startedAt,
        public readonly ?\DateTimeImmutable $completedAt,
        public readonly ?float $totalTimeSpent,
        public readonly ?int $timeLimit,
        public readonly bool $adaptiveLearning,
        public readonly array $metadata,
        public readonly array $adaptiveLearningData = []
    ) {
    }

    /**
     * Get remaining time in seconds if time limit is set.
     */
    public function getRemainingTime(): ?int
    {
        if (!$this->timeLimit || !$this->startedAt || $this->isCompleted) {
            return null;
        }

        $elapsed = time() - $this->startedAt->getTimestamp();
        $remaining = $this->timeLimit - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Check if session has timed out.
     */
    public function hasTimedOut(): bool
    {
        if (!$this->timeLimit || !$this->startedAt) {
            return false;
        }

        $elapsed = time() - $this->startedAt->getTimestamp();
        return $elapsed > $this->timeLimit;
    }

    /**
     * Get the current question or null if session is completed.
     */
    public function getCurrentQuestion(): ?array
    {
        if ($this->currentQuestionIndex >= count($this->questions)) {
            return null;
        }

        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    /**
     * Get session duration in seconds.
     */
    public function getDuration(): ?int
    {
        if (!$this->startedAt) {
            return null;
        }

        $endTime = $this->completedAt ?? new \DateTimeImmutable();
        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'currentQuestionIndex' => $this->currentQuestionIndex,
            'totalQuestions' => $this->totalQuestions,
            'progress' => $this->progress,
            'isCompleted' => $this->isCompleted,
            'isPracticeMode' => $this->isPracticeMode,
            'targetDifficulty' => $this->targetDifficulty,
            'startedAt' => $this->startedAt?->format('c'),
            'completedAt' => $this->completedAt?->format('c'),
            'totalTimeSpent' => $this->totalTimeSpent,
            'timeLimit' => $this->timeLimit,
            'remainingTime' => $this->getRemainingTime(),
            'hasTimedOut' => $this->hasTimedOut(),
            'adaptiveLearning' => $this->adaptiveLearning,
            'currentQuestion' => $this->getCurrentQuestion(),
            'metadata' => $this->metadata,
            'duration' => $this->getDuration(),
        ];
    }
}