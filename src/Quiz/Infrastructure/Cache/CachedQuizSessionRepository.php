<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Cache;

use App\Quiz\Domain\Entity\QuizSession;
use App\Quiz\Domain\Repository\QuizSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Cached quiz session repository decorator.
 * Provides intelligent caching for quiz session data.
 */
final class CachedQuizSessionRepository implements QuizSessionRepositoryInterface
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const ACTIVE_SESSION_TTL = 300; // 5 minutes for active sessions

    public function __construct(
        private readonly QuizSessionRepositoryInterface $decorated,
        private readonly TagAwareCacheInterface $cache
    ) {
    }

    public function findById(Id $id): ?QuizSession
    {
        return $this->cache->get(
            "quiz_session.{$id->toString()}",
            function (ItemInterface $item) use ($id): ?QuizSession {
                $item->expiresAfter(self::CACHE_TTL);
                $item->tag(['quiz_sessions', "session.{$id->toString()}"]);
                
                return $this->decorated->findById($id);
            }
        );
    }

    public function findActiveByUserId(UserId $userId): ?QuizSession
    {
        return $this->cache->get(
            "active_session.user.{$userId->toString()}",
            function (ItemInterface $item) use ($userId): ?QuizSession {
                $item->expiresAfter(self::ACTIVE_SESSION_TTL);
                $item->tag(['active_sessions', "user.{$userId->toString()}"]);
                
                return $this->decorated->findActiveByUserId($userId);
            }
        );
    }

    public function findByUserId(
        UserId $userId,
        ?bool $isCompleted = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $cacheKey = sprintf(
            'user_sessions.%s.%s.%s.%s',
            $userId->toString(),
            $isCompleted !== null ? ($isCompleted ? 'completed' : 'incomplete') : 'all',
            $limit ?? 'no_limit',
            $offset ?? 0
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($userId, $isCompleted, $limit, $offset): array {
                $item->expiresAfter(self::CACHE_TTL);
                $item->tag(['user_sessions', "user.{$userId->toString()}"]);
                
                return $this->decorated->findByUserId($userId, $isCompleted, $limit, $offset);
            }
        );
    }

    public function findByUserAndDateRange(
        UserId $userId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array {
        $cacheKey = sprintf(
            'user_sessions_range.%s.%s.%s',
            $userId->toString(),
            $fromDate->format('Y-m-d'),
            $toDate->format('Y-m-d')
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($userId, $fromDate, $toDate): array {
                $item->expiresAfter(self::CACHE_TTL);
                $item->tag(['user_sessions', "user.{$userId->toString()}"]);
                
                return $this->decorated->findByUserAndDateRange($userId, $fromDate, $toDate);
            }
        );
    }

    public function save(QuizSession $session): void
    {
        $this->decorated->save($session);
        
        // Invalidate relevant caches
        $userId = $session->getUserId()->toString();
        $sessionId = $session->getId()->toString();
        
        $this->cache->invalidateTags([
            "session.{$sessionId}",
            "user.{$userId}",
            'user_sessions',
            'active_sessions'
        ]);
    }

    public function delete(QuizSession $session): void
    {
        $this->decorated->delete($session);
        
        // Invalidate relevant caches
        $userId = $session->getUserId()->toString();
        $sessionId = $session->getId()->toString();
        
        $this->cache->invalidateTags([
            "session.{$sessionId}",
            "user.{$userId}",
            'user_sessions',
            'active_sessions'
        ]);
    }

    public function getUserPerformanceStats(UserId $userId): array
    {
        return $this->cache->get(
            "user_performance_stats.{$userId->toString()}",
            function (ItemInterface $item) use ($userId): array {
                $item->expiresAfter(self::CACHE_TTL);
                $item->tag(['user_performance', "user.{$userId->toString()}"]);
                
                return $this->decorated->getUserPerformanceStats($userId);
            }
        );
    }

    public function getUserLearningAnalytics(
        UserId $userId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array {
        $cacheKey = sprintf(
            'user_learning_analytics.%s.%s.%s',
            $userId->toString(),
            $fromDate?->format('Y-m-d') ?? 'no_start',
            $toDate?->format('Y-m-d') ?? 'no_end'
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($userId, $fromDate, $toDate): array {
                $item->expiresAfter(300); // 5 minutes for analytics
                $item->tag(['learning_analytics', "user.{$userId->toString()}"]);
                
                return $this->decorated->getUserLearningAnalytics($userId, $fromDate, $toDate);
            }
        );
    }

    public function getAdaptiveLearningData(
        UserId $userId,
        ?int $categoryId = null,
        int $limit = 100
    ): array {
        $cacheKey = sprintf(
            'adaptive_learning_data.%s.%s.%d',
            $userId->toString(),
            $categoryId ?? 'all_categories',
            $limit
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($userId, $categoryId, $limit): array {
                $item->expiresAfter(600); // 10 minutes for adaptive data
                $item->tag(['adaptive_learning', "user.{$userId->toString()}"]);
                
                return $this->decorated->getAdaptiveLearningData($userId, $categoryId, $limit);
            }
        );
    }

    public function getSessionProgressAnalytics(Id $sessionId): array
    {
        return $this->cache->get(
            "session_progress_analytics.{$sessionId->toString()}",
            function (ItemInterface $item) use ($sessionId): array {
                $item->expiresAfter(60); // 1 minute for real-time progress
                $item->tag(['session_progress', "session.{$sessionId->toString()}"]);
                
                return $this->decorated->getSessionProgressAnalytics($sessionId);
            }
        );
    }

    public function findSimilarUsers(UserId $userId, int $limit = 10): array
    {
        return $this->cache->get(
            "similar_users.{$userId->toString()}.{$limit}",
            function (ItemInterface $item) use ($userId, $limit): array {
                $item->expiresAfter(3600); // 1 hour for similar users
                $item->tag(['similar_users', "user.{$userId->toString()}"]);
                
                return $this->decorated->findSimilarUsers($userId, $limit);
            }
        );
    }

    /**
     * Warm up cache for a user's critical data.
     */
    public function warmupUserCache(UserId $userId): void
    {
        // Pre-load critical user data
        $this->findActiveByUserId($userId);
        $this->getUserPerformanceStats($userId);
        $this->getAdaptiveLearningData($userId);
        $this->findByUserId($userId, null, 10); // Recent 10 sessions
    }

    /**
     * Clear all cache for a user.
     */
    public function clearUserCache(UserId $userId): void
    {
        $this->cache->invalidateTags(["user.{$userId->toString()}"]);
    }
}