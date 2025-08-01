<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Centralized cache invalidation manager.
 * Handles event-driven cache invalidation and warming strategies.
 */
final class CacheInvalidationManager implements EventSubscriberInterface
{
    public function __construct(
        private readonly TagAwareCacheInterface $tagAwareCache,
        private readonly QuizCacheService $quizCacheService,
        // private readonly AnalyticsCacheService $analyticsCacheService,
        private readonly UserCacheService $userCacheService,
        private readonly LeaderboardCacheService $leaderboardCacheService,
        private readonly CacheWarmupService $cacheWarmupService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get subscribed events for automatic cache invalidation.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Quiz events
            'quiz.session.started' => 'onQuizSessionStarted',
            'quiz.session.completed' => 'onQuizSessionCompleted',
            'quiz.question.answered' => 'onQuizQuestionAnswered',
            'quiz.attempt.completed' => 'onQuizAttemptCompleted',
            
            // User events
            'user.profile.updated' => 'onUserProfileUpdated',
            'user.preferences.changed' => 'onUserPreferencesChanged',
            'user.achievement.earned' => 'onUserAchievementEarned',
            'user.logged.in' => 'onUserLoggedIn',
            'user.logged.out' => 'onUserLoggedOut',
            
            // Analytics events
            'analytics.metrics.updated' => 'onAnalyticsMetricsUpdated',
            'leaderboard.position.changed' => 'onLeaderboardPositionChanged',
            'competition.started' => 'onCompetitionStarted',
            'competition.ended' => 'onCompetitionEnded',
            
            // System events
            'cache.warm.requested' => 'onCacheWarmRequested',
            'cache.clear.requested' => 'onCacheClearRequested',
        ];
    }

    /**
     * Handle quiz session started event.
     */
    public function onQuizSessionStarted($event): void
    {
        $sessionId = $event->getSessionId();
        $userId = $event->getUserId();
        
        try {
            // Invalidate user's previous session data
            $this->quizCacheService->invalidateUserQuizCache($userId);
            
            // Warm cache for new session
            $this->cacheWarmupService->warmQuizSession($sessionId);
            
            $this->logger->info('Cache invalidated for quiz session start', [
                'session_id' => $sessionId,
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle quiz session start cache invalidation', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle quiz session completed event.
     */
    public function onQuizSessionCompleted($event): void
    {
        $sessionId = $event->getSessionId();
        $userId = $event->getUserId();
        $categoryId = $event->getCategoryId();
        
        try {
            // Invalidate related caches
            $this->quizCacheService->invalidateUserQuizCache($userId);
            $this->analyticsCacheService->invalidateUserAnalytics($userId);
            $this->userCacheService->invalidateUserCache($userId);
            
            // Invalidate category-specific data
            if ($categoryId) {
                $this->quizCacheService->invalidateCategoryQuizCache($categoryId);
            }
            
            // Invalidate leaderboards
            $this->leaderboardCacheService->invalidateLeaderboardCache([
                'user_id' => $userId
            ]);
            
            // Schedule cache warming for updated data
            $this->scheduleAsyncCacheWarming([
                'user_analytics' => $userId,
                'user_progress' => $userId,
                'category_leaderboard' => $categoryId
            ]);
            
            $this->logger->info('Cache invalidated for quiz session completion', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'category_id' => $categoryId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle quiz session completion cache invalidation', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle quiz question answered event.
     */
    public function onQuizQuestionAnswered($event): void
    {
        $questionId = $event->getQuestionId();
        $userId = $event->getUserId();
        $isCorrect = $event->isCorrect();
        
        try {
            // Only invalidate real-time analytics for immediate feedback
            $this->analyticsCacheService->invalidateRealTimeAnalytics();
            
            // Update user position if it might have changed significantly
            if ($isCorrect) {
                $this->scheduleLeaderboardUpdate($userId);
            }
            
            $this->logger->debug('Cache updated for question answered', [
                'question_id' => $questionId,
                'user_id' => $userId,
                'is_correct' => $isCorrect
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle question answered cache update', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle user profile updated event.
     */
    public function onUserProfileUpdated($event): void
    {
        $userId = $event->getUserId();
        $updatedFields = $event->getUpdatedFields();
        
        try {
            // Full user cache invalidation
            $this->userCacheService->invalidateUserCache($userId);
            
            // If role changed, invalidate analytics with different TTL
            if (in_array('role', $updatedFields)) {
                $this->analyticsCacheService->invalidateUserAnalytics($userId);
            }
            
            // Warm updated profile data
            $this->cacheWarmupService->warmUserProfile($userId);
            
            $this->logger->info('Cache invalidated for user profile update', [
                'user_id' => $userId,
                'updated_fields' => $updatedFields
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle user profile update cache invalidation', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle leaderboard position changed event.
     */
    public function onLeaderboardPositionChanged($event): void
    {
        $userId = $event->getUserId();
        $leaderboardType = $event->getLeaderboardType();
        $oldPosition = $event->getOldPosition();
        $newPosition = $event->getNewPosition();
        
        try {
            // Update specific user position
            $this->leaderboardCacheService->updateUserPosition(
                $userId,
                $leaderboardType,
                $newPosition,
                ['previous_position' => $oldPosition]
            );
            
            // If significant position change, invalidate full leaderboard
            $positionDifference = abs($newPosition - $oldPosition);
            if ($positionDifference > 5 || $newPosition <= 10 || $oldPosition <= 10) {
                $this->leaderboardCacheService->invalidateLeaderboardCache([
                    'type' => $leaderboardType
                ]);
            }
            
            $this->logger->info('Cache updated for leaderboard position change', [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
                'position_difference' => $positionDifference
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle leaderboard position change', [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle competition started event.
     */
    public function onCompetitionStarted($event): void
    {
        $competitionId = $event->getCompetitionId();
        $competitionType = $event->getCompetitionType();
        
        try {
            // Invalidate all leaderboard caches for fresh competitive data
            $this->leaderboardCacheService->invalidateLeaderboardCache([
                'live_only' => true
            ]);
            
            // Enable real-time metrics for competition
            $this->analyticsCacheService->invalidateRealTimeAnalytics();
            
            // Pre-warm competition-specific data
            $this->cacheWarmupService->warmCompetitionData($competitionId, $competitionType);
            
            $this->logger->info('Cache prepared for competition start', [
                'competition_id' => $competitionId,
                'competition_type' => $competitionType
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to prepare cache for competition start', [
                'competition_id' => $competitionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle analytics metrics updated event.
     */
    public function onAnalyticsMetricsUpdated($event): void
    {
        $metricsType = $event->getMetricsType();
        $affectedUsers = $event->getAffectedUsers();
        
        try {
            // Invalidate specific analytics based on type
            $criteria = ['metrics_type' => $metricsType];
            
            if (!empty($affectedUsers)) {
                foreach ($affectedUsers as $userId) {
                    $this->analyticsCacheService->invalidateUserAnalytics($userId);
                }
            } else {
                // Global metrics update
                $this->analyticsCacheService->invalidateAnalyticsCache();
            }
            
            $this->logger->info('Cache invalidated for analytics metrics update', [
                'metrics_type' => $metricsType,
                'affected_users_count' => count($affectedUsers)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle analytics metrics update', [
                'metrics_type' => $metricsType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle cache warm requested event.
     */
    public function onCacheWarmRequested($event): void
    {
        $warmupType = $event->getWarmupType();
        $parameters = $event->getParameters();
        
        try {
            switch ($warmupType) {
                case 'user_data':
                    $this->cacheWarmupService->warmUserData($parameters['user_ids'] ?? []);
                    break;
                case 'quiz_data':
                    $this->cacheWarmupService->warmQuizData($parameters['categories'] ?? []);
                    break;
                case 'analytics_data':
                    $this->cacheWarmupService->warmAnalyticsData($parameters['types'] ?? []);
                    break;
                case 'leaderboard_data':
                    $this->cacheWarmupService->warmLeaderboardData($parameters['types'] ?? []);
                    break;
                case 'all':
                    $this->cacheWarmupService->warmAllCaches();
                    break;
            }
            
            $this->logger->info('Cache warming completed', [
                'warmup_type' => $warmupType,
                'parameters' => $parameters
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to warm cache', [
                'warmup_type' => $warmupType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle cache clear requested event.
     */
    public function onCacheClearRequested($event): void
    {
        $clearType = $event->getClearType();
        $parameters = $event->getParameters();
        
        try {
            switch ($clearType) {
                case 'quiz':
                    $this->quizCacheService->invalidateQuizCache();
                    break;
                case 'analytics':
                    $this->analyticsCacheService->invalidateAnalyticsCache();
                    break;
                case 'user':
                    if (isset($parameters['user_id'])) {
                        $this->userCacheService->invalidateUserCache($parameters['user_id']);
                    } else {
                        $this->tagAwareCache->invalidateTags(['user']);
                    }
                    break;
                case 'leaderboard':
                    $this->leaderboardCacheService->invalidateLeaderboardCache();
                    break;
                case 'all':
                    $this->tagAwareCache->clear();
                    break;
            }
            
            $this->logger->info('Cache cleared', [
                'clear_type' => $clearType,
                'parameters' => $parameters
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear cache', [
                'clear_type' => $clearType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manually invalidate cache by tags.
     */
    public function invalidateByTags(array $tags, string $reason = ''): bool
    {
        try {
            $result = $this->tagAwareCache->invalidateTags($tags);
            
            $this->logger->info('Manual cache invalidation by tags', [
                'tags' => $tags,
                'reason' => $reason,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to manually invalidate cache by tags', [
                'tags' => $tags,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Bulk invalidation for maintenance operations.
     */
    public function bulkInvalidate(array $operations): array
    {
        $results = [];
        
        foreach ($operations as $operation) {
            $type = $operation['type'];
            $criteria = $operation['criteria'] ?? [];
            
            try {
                $success = match ($type) {
                    'quiz' => $this->quizCacheService->invalidateQuizCache($criteria['tags'] ?? []),
                    'analytics' => $this->analyticsCacheService->invalidateAnalyticsCache($criteria),
                    'user' => isset($criteria['user_id']) 
                        ? $this->userCacheService->invalidateUserCache($criteria['user_id'])
                        : $this->invalidateByTags(['user']),
                    'leaderboard' => $this->leaderboardCacheService->invalidateLeaderboardCache($criteria),
                    'tags' => $this->invalidateByTags($criteria['tags'] ?? []),
                    default => false
                };
                
                $results[] = ['type' => $type, 'criteria' => $criteria, 'success' => $success];
            } catch (\Exception $e) {
                $results[] = [
                    'type' => $type, 
                    'criteria' => $criteria, 
                    'success' => false, 
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $successful = count(array_filter($results, fn($r) => $r['success']));
        $this->logger->info('Bulk cache invalidation completed', [
            'total_operations' => count($operations),
            'successful' => $successful,
            'failed' => count($operations) - $successful
        ]);
        
        return $results;
    }

    private function scheduleAsyncCacheWarming(array $warmupTasks): void
    {
        // This would typically dispatch async messages for cache warming
        // For now, we'll just log the intent
        $this->logger->info('Async cache warming scheduled', [
            'tasks' => array_keys($warmupTasks)
        ]);
    }

    private function scheduleLeaderboardUpdate(string $userId): void
    {
        // This would typically check if the user's score change warrants a position update
        // and schedule an async leaderboard recalculation
        $this->logger->debug('Leaderboard update scheduled', [
            'user_id' => $userId
        ]);
    }
}