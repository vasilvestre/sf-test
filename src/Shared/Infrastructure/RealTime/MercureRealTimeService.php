<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RealTime;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Real-time notification service using Mercure.
 * Provides live updates for quiz sessions, analytics, and user interactions.
 */
final class MercureRealTimeService
{
    public function __construct(
        private readonly HubInterface $hub
    ) {
    }

    /**
     * Publish quiz session update.
     */
    public function publishQuizSessionUpdate(string $sessionId, array $data): void
    {
        $update = new Update(
            topic: "quiz/session/{$sessionId}",
            data: json_encode([
                'type' => 'quiz_session_update',
                'sessionId' => $sessionId,
                'data' => $data,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish question answer result.
     */
    public function publishQuestionAnswered(string $sessionId, array $answerData): void
    {
        $update = new Update(
            topic: "quiz/session/{$sessionId}/questions",
            data: json_encode([
                'type' => 'question_answered',
                'sessionId' => $sessionId,
                'answerData' => $answerData,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish quiz completion.
     */
    public function publishQuizCompleted(string $sessionId, array $results): void
    {
        $update = new Update(
            topic: "quiz/session/{$sessionId}/completed",
            data: json_encode([
                'type' => 'quiz_completed',
                'sessionId' => $sessionId,
                'results' => $results,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish leaderboard update.
     */
    public function publishLeaderboardUpdate(string $categoryId, array $leaderboard): void
    {
        $topic = $categoryId ? "leaderboard/category/{$categoryId}" : "leaderboard/global";
        
        $update = new Update(
            topic: $topic,
            data: json_encode([
                'type' => 'leaderboard_update',
                'categoryId' => $categoryId,
                'leaderboard' => $leaderboard,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish analytics update.
     */
    public function publishAnalyticsUpdate(string $userId, array $analytics): void
    {
        $update = new Update(
            topic: "analytics/user/{$userId}",
            data: json_encode([
                'type' => 'analytics_update',
                'userId' => $userId,
                'analytics' => $analytics,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish system notification.
     */
    public function publishSystemNotification(array $notification, array $targetUsers = []): void
    {
        $topics = empty($targetUsers) 
            ? ['notifications/system']
            : array_map(fn($userId) => "notifications/user/{$userId}", $targetUsers);

        foreach ($topics as $topic) {
            $update = new Update(
                topic: $topic,
                data: json_encode([
                    'type' => 'system_notification',
                    'notification' => $notification,
                    'timestamp' => time(),
                ])
            );

            $this->hub->publish($update);
        }
    }

    /**
     * Publish user achievement.
     */
    public function publishUserAchievement(string $userId, array $achievement): void
    {
        $update = new Update(
            topic: "achievements/user/{$userId}",
            data: json_encode([
                'type' => 'achievement_unlocked',
                'userId' => $userId,
                'achievement' => $achievement,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish real-time quiz statistics.
     */
    public function publishQuizStatistics(array $statistics): void
    {
        $update = new Update(
            topic: 'statistics/real-time',
            data: json_encode([
                'type' => 'statistics_update',
                'statistics' => $statistics,
                'timestamp' => time(),
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Get subscription URL for a topic.
     */
    public function getSubscriptionUrl(string $topic): string
    {
        return $this->hub->getUrl() . '?' . http_build_query(['topic' => $topic]);
    }

    /**
     * Generate JWT for private subscriptions.
     */
    public function generateSubscriptionToken(array $topics): string
    {
        // This would be implemented based on your JWT configuration
        // For now, returning a placeholder
        return $this->hub->getProvider()->getJwt(['subscribe' => $topics]);
    }
}