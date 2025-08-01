<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;

/**
 * Domain event fired when a quiz session is completed.
 * Captures the final results and performance metrics of a quiz session.
 */
final class QuizSessionCompleted extends AbstractDomainEvent
{
    public function __construct(
        private readonly Id $sessionId,
        private readonly UserId $userId,
        private readonly float $finalScore,
        private readonly int $correctAnswers,
        private readonly int $totalQuestions,
        private readonly float $totalTimeSpent,
        private readonly array $adaptiveLearningData,
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

    public function getFinalScore(): float
    {
        return $this->finalScore;
    }

    public function getCorrectAnswers(): int
    {
        return $this->correctAnswers;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function getTotalTimeSpent(): float
    {
        return $this->totalTimeSpent;
    }

    public function getAdaptiveLearningData(): array
    {
        return $this->adaptiveLearningData;
    }

    /**
     * Calculate the accuracy percentage.
     */
    public function getAccuracy(): float
    {
        if ($this->totalQuestions === 0) {
            return 0.0;
        }
        
        return ($this->correctAnswers / $this->totalQuestions) * 100;
    }

    /**
     * Calculate average time per question.
     */
    public function getAverageTimePerQuestion(): float
    {
        if ($this->totalQuestions === 0) {
            return 0.0;
        }
        
        return $this->totalTimeSpent / $this->totalQuestions;
    }

    /**
     * Determine performance level based on score.
     */
    public function getPerformanceLevel(): string
    {
        return match (true) {
            $this->finalScore >= 90 => 'excellent',
            $this->finalScore >= 80 => 'good',
            $this->finalScore >= 70 => 'satisfactory',
            $this->finalScore >= 60 => 'needs_improvement',
            default => 'poor'
        };
    }

    /**
     * Check if this was a successful completion.
     */
    public function isSuccessful(float $passingScore = 70.0): bool
    {
        return $this->finalScore >= $passingScore;
    }

    public function getEventName(): string
    {
        return 'quiz.session.completed';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'session_id' => $this->sessionId->toString(),
            'user_id' => $this->userId->toString(),
            'final_score' => $this->finalScore,
            'correct_answers' => $this->correctAnswers,
            'total_questions' => $this->totalQuestions,
            'total_time_spent' => $this->totalTimeSpent,
            'accuracy' => $this->getAccuracy(),
            'average_time_per_question' => $this->getAverageTimePerQuestion(),
            'performance_level' => $this->getPerformanceLevel(),
            'adaptive_learning_data_count' => count($this->adaptiveLearningData),
        ]);
    }

    /**
     * Get comprehensive analytics payload for this event.
     */
    public function getAnalyticsPayload(): array
    {
        return [
            'event_type' => 'quiz_session_completed',
            'session_id' => $this->sessionId->toString(),
            'user_id' => $this->userId->toString(),
            'results' => [
                'final_score' => $this->finalScore,
                'correct_answers' => $this->correctAnswers,
                'total_questions' => $this->totalQuestions,
                'accuracy_percentage' => $this->getAccuracy(),
                'performance_level' => $this->getPerformanceLevel(),
                'is_successful' => $this->isSuccessful(),
            ],
            'timing' => [
                'total_time_spent' => $this->totalTimeSpent,
                'average_time_per_question' => $this->getAverageTimePerQuestion(),
            ],
            'adaptive_learning' => [
                'data_points_collected' => count($this->adaptiveLearningData),
                'learning_trajectory' => $this->extractLearningTrajectory(),
            ],
            'timestamp' => $this->getOccurredOn()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Extract learning trajectory from adaptive learning data.
     */
    private function extractLearningTrajectory(): array
    {
        if (empty($this->adaptiveLearningData)) {
            return [];
        }

        $trajectory = [];
        $runningCorrect = 0;
        $total = 0;

        foreach ($this->adaptiveLearningData as $dataPoint) {
            $total++;
            if ($dataPoint['correct'] ?? false) {
                $runningCorrect++;
            }
            
            $trajectory[] = [
                'question_number' => $total,
                'was_correct' => $dataPoint['correct'] ?? false,
                'cumulative_accuracy' => ($runningCorrect / $total) * 100,
                'difficulty_level' => $dataPoint['difficulty'] ?? 'unknown',
                'time_spent' => $dataPoint['timeSpent'] ?? 0,
            ];
        }

        return $trajectory;
    }

    /**
     * Get recommendations based on performance.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->finalScore < 70) {
            $recommendations[] = 'Consider reviewing the material before taking another quiz';
            $recommendations[] = 'Practice mode might help improve understanding';
        }
        
        if ($this->getAverageTimePerQuestion() < 15) {
            $recommendations[] = 'Take more time to carefully read and consider each question';
        }
        
        if ($this->getAverageTimePerQuestion() > 120) {
            $recommendations[] = 'Try to answer questions more efficiently to better manage time';
        }
        
        if ($this->finalScore >= 90) {
            $recommendations[] = 'Excellent performance! Consider trying more challenging topics';
        }
        
        return $recommendations;
    }
}