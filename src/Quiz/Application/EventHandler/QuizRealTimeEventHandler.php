<?php

declare(strict_types=1);

namespace App\Quiz\Application\EventHandler;

use App\Quiz\Domain\Event\QuizSessionCompleted;
use App\Quiz\Domain\Event\QuestionAnswered;
use App\Quiz\Domain\Event\QuizSessionStarted;
use App\Shared\Infrastructure\RealTime\MercureRealTimeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Event handler for real-time quiz updates via Mercure.
 */
final class QuizRealTimeEventHandler
{
    public function __construct(
        private readonly MercureRealTimeService $realTimeService
    ) {
    }

    #[AsEventListener]
    public function onQuizSessionStarted(QuizSessionStarted $event): void
    {
        $this->realTimeService->publishQuizSessionUpdate(
            $event->getSessionId()->toString(),
            [
                'status' => 'started',
                'userId' => $event->getUserId()->toString(),
                'questionCount' => $event->getQuestionCount(),
                'difficulty' => $event->getTargetDifficulty()->getLevel(),
                'adaptiveLearning' => $event->isAdaptiveLearning(),
                'practiceMode' => $event->isPracticeMode(),
            ]
        );

        // Update real-time statistics
        $this->realTimeService->publishQuizStatistics([
            'activeUsers' => $this->getActiveUsersCount(),
            'sessionsStarted' => $this->getSessionsStartedToday(),
        ]);
    }

    #[AsEventListener]
    public function onQuestionAnswered(QuestionAnswered $event): void
    {
        $this->realTimeService->publishQuestionAnswered(
            $event->getSessionId()->toString(),
            [
                'questionId' => $event->getQuestionId()->toString(),
                'answers' => $event->getAnswers(),
                'isCorrect' => $event->isCorrect(),
                'score' => $event->getScore(),
                'timeSpent' => $event->getTimeSpent(),
            ]
        );

        // Update session progress in real-time
        $this->realTimeService->publishQuizSessionUpdate(
            $event->getSessionId()->toString(),
            [
                'status' => 'in_progress',
                'lastAnswered' => $event->getQuestionId()->toString(),
                'currentScore' => $this->getCurrentSessionScore($event->getSessionId()),
            ]
        );
    }

    #[AsEventListener]
    public function onQuizSessionCompleted(QuizSessionCompleted $event): void
    {
        $sessionId = $event->getSessionId()->toString();
        $userId = $event->getUserId()->toString();

        // Publish quiz completion
        $this->realTimeService->publishQuizCompleted($sessionId, [
            'finalScore' => $event->getFinalScore(),
            'correctAnswers' => $event->getCorrectAnswers(),
            'totalQuestions' => $event->getTotalQuestions(),
            'timeSpent' => $event->getTimeSpent(),
            'adaptiveLearningData' => $event->getAdaptiveLearningData(),
        ]);

        // Update user analytics in real-time
        $this->realTimeService->publishAnalyticsUpdate($userId, [
            'newSession' => [
                'score' => $event->getFinalScore(),
                'timeSpent' => $event->getTimeSpent(),
                'completedAt' => time(),
            ],
            'performance' => $this->getUserPerformanceUpdate($userId),
        ]);

        // Check for achievements
        $achievements = $this->checkForNewAchievements($event);
        foreach ($achievements as $achievement) {
            $this->realTimeService->publishUserAchievement($userId, $achievement);
        }

        // Update leaderboards if not practice mode
        if (!$this->isPracticeMode($sessionId)) {
            $this->updateLeaderboards($event);
        }

        // Update real-time statistics
        $this->realTimeService->publishQuizStatistics([
            'totalCompletions' => $this->getTotalCompletionsToday(),
            'averageScore' => $this->getAverageScoreToday(),
            'activeUsers' => $this->getActiveUsersCount(),
        ]);
    }

    private function getCurrentSessionScore(string $sessionId): float
    {
        // Implementation would fetch current session score
        // For now, returning a placeholder
        return 0.0;
    }

    private function getUserPerformanceUpdate(string $userId): array
    {
        // Implementation would calculate updated performance metrics
        return [
            'averageScore' => 0.0,
            'totalSessions' => 0,
            'improvementTrend' => 'positive',
        ];
    }

    private function checkForNewAchievements(QuizSessionCompleted $event): array
    {
        $achievements = [];

        // Check for perfect score achievement
        if ($event->getFinalScore() >= 100.0) {
            $achievements[] = [
                'id' => 'perfect_score',
                'title' => 'Perfect Score!',
                'description' => 'You got a perfect score on a quiz',
                'type' => 'performance',
                'rarity' => 'rare',
            ];
        }

        // Check for speed achievement
        $averageTimePerQuestion = $event->getTimeSpent() / $event->getTotalQuestions();
        if ($averageTimePerQuestion < 30 && $event->getFinalScore() > 80) {
            $achievements[] = [
                'id' => 'speed_demon',
                'title' => 'Speed Demon',
                'description' => 'Completed quiz with high score in record time',
                'type' => 'speed',
                'rarity' => 'uncommon',
            ];
        }

        // Check for consistency achievement
        $adaptiveData = $event->getAdaptiveLearningData();
        if ($this->isConsistentPerformance($adaptiveData)) {
            $achievements[] = [
                'id' => 'consistent_performer',
                'title' => 'Consistent Performer',
                'description' => 'Maintained steady performance throughout the quiz',
                'type' => 'consistency',
                'rarity' => 'common',
            ];
        }

        return $achievements;
    }

    private function updateLeaderboards(QuizSessionCompleted $event): void
    {
        // Update global leaderboard
        $globalLeaderboard = $this->calculateGlobalLeaderboard();
        $this->realTimeService->publishLeaderboardUpdate(null, $globalLeaderboard);

        // Update category-specific leaderboards if applicable
        $categoryId = $this->getSessionCategory($event->getSessionId());
        if ($categoryId) {
            $categoryLeaderboard = $this->calculateCategoryLeaderboard($categoryId);
            $this->realTimeService->publishLeaderboardUpdate($categoryId, $categoryLeaderboard);
        }
    }

    private function isPracticeMode(string $sessionId): bool
    {
        // Implementation would check if session is in practice mode
        return false;
    }

    private function isConsistentPerformance(array $adaptiveData): bool
    {
        if (empty($adaptiveData) || count($adaptiveData) < 5) {
            return false;
        }

        $scores = array_column($adaptiveData, 'correct');
        $variance = $this->calculateVariance($scores);
        
        return $variance < 0.2; // Low variance indicates consistency
    }

    private function calculateVariance(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return array_sum($squaredDiffs) / count($squaredDiffs);
    }

    private function getActiveUsersCount(): int
    {
        // Implementation would count active users
        return rand(10, 100); // Placeholder
    }

    private function getSessionsStartedToday(): int
    {
        // Implementation would count sessions started today
        return rand(50, 200); // Placeholder
    }

    private function getTotalCompletionsToday(): int
    {
        // Implementation would count completions today
        return rand(30, 150); // Placeholder
    }

    private function getAverageScoreToday(): float
    {
        // Implementation would calculate average score today
        return rand(60, 90) + (rand(0, 99) / 100); // Placeholder
    }

    private function calculateGlobalLeaderboard(): array
    {
        // Implementation would calculate actual leaderboard
        return [
            ['userId' => 'user1', 'score' => 98.5, 'rank' => 1],
            ['userId' => 'user2', 'score' => 95.2, 'rank' => 2],
            ['userId' => 'user3', 'score' => 92.8, 'rank' => 3],
        ];
    }

    private function calculateCategoryLeaderboard(string $categoryId): array
    {
        // Implementation would calculate category-specific leaderboard
        return [
            ['userId' => 'user1', 'score' => 96.3, 'rank' => 1, 'categoryId' => $categoryId],
            ['userId' => 'user2', 'score' => 93.7, 'rank' => 2, 'categoryId' => $categoryId],
        ];
    }

    private function getSessionCategory(string $sessionId): ?string
    {
        // Implementation would get session category
        return null; // Placeholder
    }
}