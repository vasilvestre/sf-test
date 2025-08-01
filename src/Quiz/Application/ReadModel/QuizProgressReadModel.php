<?php

declare(strict_types=1);

namespace App\Quiz\Application\ReadModel;

/**
 * Read model for quiz progress data.
 * Provides detailed progress tracking and analytics.
 */
final class QuizProgressReadModel
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $userId,
        public readonly int $totalQuestions,
        public readonly int $answeredQuestions,
        public readonly int $correctAnswers,
        public readonly int $incorrectAnswers,
        public readonly float $completionPercentage,
        public readonly float $accuracyPercentage,
        public readonly float $averageTimePerQuestion,
        public readonly array $questionProgress,
        public readonly array $categoryProgress,
        public readonly array $difficultyProgress,
        public readonly ?float $estimatedTimeToComplete,
        public readonly array $streakData,
        public readonly array $recentPerformance,
        public readonly ?\DateTimeImmutable $lastUpdated
    ) {
    }

    /**
     * Get questions remaining in the session.
     */
    public function getQuestionsRemaining(): int
    {
        return $this->totalQuestions - $this->answeredQuestions;
    }

    /**
     * Check if user is on a correct answer streak.
     */
    public function isOnStreak(): bool
    {
        return ($this->streakData['current'] ?? 0) > 0;
    }

    /**
     * Get current streak length.
     */
    public function getCurrentStreak(): int
    {
        return $this->streakData['current'] ?? 0;
    }

    /**
     * Get performance trend (improving, declining, stable).
     */
    public function getPerformanceTrend(): string
    {
        if (count($this->recentPerformance) < 3) {
            return 'insufficient_data';
        }

        $recent = array_slice($this->recentPerformance, -3);
        $trend = 0;

        for ($i = 1; $i < count($recent); $i++) {
            if ($recent[$i] > $recent[$i - 1]) {
                $trend++;
            } elseif ($recent[$i] < $recent[$i - 1]) {
                $trend--;
            }
        }

        return match (true) {
            $trend > 0 => 'improving',
            $trend < 0 => 'declining',
            default => 'stable',
        };
    }

    /**
     * Get the weakest category based on performance.
     */
    public function getWeakestCategory(): ?array
    {
        if (empty($this->categoryProgress)) {
            return null;
        }

        $weakest = null;
        $lowestAccuracy = 100.0;

        foreach ($this->categoryProgress as $category) {
            if ($category['accuracy'] < $lowestAccuracy) {
                $lowestAccuracy = $category['accuracy'];
                $weakest = $category;
            }
        }

        return $weakest;
    }

    /**
     * Get the strongest category based on performance.
     */
    public function getStrongestCategory(): ?array
    {
        if (empty($this->categoryProgress)) {
            return null;
        }

        $strongest = null;
        $highestAccuracy = 0.0;

        foreach ($this->categoryProgress as $category) {
            if ($category['accuracy'] > $highestAccuracy) {
                $highestAccuracy = $category['accuracy'];
                $strongest = $category;
            }
        }

        return $strongest;
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'userId' => $this->userId,
            'totalQuestions' => $this->totalQuestions,
            'answeredQuestions' => $this->answeredQuestions,
            'questionsRemaining' => $this->getQuestionsRemaining(),
            'correctAnswers' => $this->correctAnswers,
            'incorrectAnswers' => $this->incorrectAnswers,
            'completionPercentage' => $this->completionPercentage,
            'accuracyPercentage' => $this->accuracyPercentage,
            'averageTimePerQuestion' => $this->averageTimePerQuestion,
            'estimatedTimeToComplete' => $this->estimatedTimeToComplete,
            'currentStreak' => $this->getCurrentStreak(),
            'isOnStreak' => $this->isOnStreak(),
            'performanceTrend' => $this->getPerformanceTrend(),
            'questionProgress' => $this->questionProgress,
            'categoryProgress' => $this->categoryProgress,
            'difficultyProgress' => $this->difficultyProgress,
            'weakestCategory' => $this->getWeakestCategory(),
            'strongestCategory' => $this->getStrongestCategory(),
            'streakData' => $this->streakData,
            'recentPerformance' => $this->recentPerformance,
            'lastUpdated' => $this->lastUpdated?->format('c'),
        ];
    }
}