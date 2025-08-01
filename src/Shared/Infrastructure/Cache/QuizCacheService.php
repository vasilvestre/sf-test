<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Comprehensive caching service for quiz-related data.
 * Handles quiz sessions, questions, answers, and results with intelligent TTL strategies.
 */
final class QuizCacheService implements CacheConfigurationInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $sessionCache,
        private readonly CacheItemPoolInterface $questionCache,
        private readonly TagAwareCacheInterface $tagAwareCache,
        private readonly CacheMetricsCollector $metricsCollector,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Cache quiz session data with adaptive TTL based on session state.
     */
    public function cacheQuizSession(string $sessionId, array $sessionData, bool $isActive = true): bool
    {
        try {
            $cacheKey = self::QUIZ_SESSION_PREFIX . $sessionId;
            $item = $this->sessionCache->getItem($cacheKey);
            
            // Adaptive TTL based on session state
            $ttl = $isActive ? self::QUIZ_SESSION_TTL : (self::QUIZ_SESSION_TTL * 2);
            
            $item->set($sessionData);
            $item->expiresAfter($ttl);
            $item->tag([self::TAG_QUIZ, self::TAG_SESSION, "user:{$sessionData['user_id']}"]);
            
            $success = $this->sessionCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('quiz_session', 'set', $success);
            $this->logger->debug('Quiz session cached', [
                'session_id' => $sessionId,
                'ttl' => $ttl,
                'is_active' => $isActive,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache quiz session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve quiz session from cache with fallback strategy.
     */
    public function getQuizSession(string $sessionId): ?array
    {
        try {
            $cacheKey = self::QUIZ_SESSION_PREFIX . $sessionId;
            $item = $this->sessionCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('quiz_session', 'hit', true);
                $this->logger->debug('Quiz session cache hit', ['session_id' => $sessionId]);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('quiz_session', 'miss', true);
            $this->logger->debug('Quiz session cache miss', ['session_id' => $sessionId]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve quiz session from cache', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache question data with content-based TTL.
     */
    public function cacheQuestion(string $questionId, array $questionData, bool $includeAnswers = false): bool
    {
        try {
            $cacheKey = self::QUIZ_QUESTION_PREFIX . $questionId;
            $item = $this->questionCache->getItem($cacheKey);
            
            // Longer TTL for questions with answers (more expensive to regenerate)
            $ttl = $includeAnswers ? (self::QUIZ_QUESTION_TTL * 2) : self::QUIZ_QUESTION_TTL;
            
            $item->set($questionData);
            $item->expiresAfter($ttl);
            $item->tag([
                self::TAG_QUIZ, 
                self::TAG_QUESTION,
                "category:{$questionData['category_id']}",
                "difficulty:{$questionData['difficulty']}"
            ]);
            
            $success = $this->questionCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('quiz_question', 'set', $success);
            $this->logger->debug('Question cached', [
                'question_id' => $questionId,
                'ttl' => $ttl,
                'include_answers' => $includeAnswers,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache question', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve question from cache.
     */
    public function getQuestion(string $questionId): ?array
    {
        try {
            $cacheKey = self::QUIZ_QUESTION_PREFIX . $questionId;
            $item = $this->questionCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('quiz_question', 'hit', true);
                $this->logger->debug('Question cache hit', ['question_id' => $questionId]);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('quiz_question', 'miss', true);
            $this->logger->debug('Question cache miss', ['question_id' => $questionId]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve question from cache', [
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache multiple questions in batch for efficiency.
     */
    public function cacheQuestionsBatch(array $questions): array
    {
        $results = [];
        
        foreach ($questions as $questionId => $questionData) {
            $results[$questionId] = $this->cacheQuestion($questionId, $questionData);
        }
        
        $this->logger->info('Questions batch cached', [
            'total' => count($questions),
            'successful' => count(array_filter($results)),
            'failed' => count(array_filter($results, fn($r) => !$r))
        ]);
        
        return $results;
    }

    /**
     * Retrieve multiple questions in batch.
     */
    public function getQuestionsBatch(array $questionIds): array
    {
        $results = [];
        $hits = 0;
        $misses = 0;
        
        foreach ($questionIds as $questionId) {
            $question = $this->getQuestion($questionId);
            if ($question !== null) {
                $results[$questionId] = $question;
                $hits++;
            } else {
                $misses++;
            }
        }
        
        $this->logger->info('Questions batch retrieved', [
            'requested' => count($questionIds),
            'hits' => $hits,
            'misses' => $misses,
            'hit_ratio' => $hits / max(1, count($questionIds))
        ]);
        
        return $results;
    }

    /**
     * Invalidate quiz-related cache by tags.
     */
    public function invalidateQuizCache(array $tags = []): bool
    {
        try {
            $tagsToInvalidate = array_merge([self::TAG_QUIZ], $tags);
            $result = $this->tagAwareCache->invalidateTags($tagsToInvalidate);
            
            $this->logger->info('Quiz cache invalidated', [
                'tags' => $tagsToInvalidate,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate quiz cache', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Invalidate user-specific quiz cache.
     */
    public function invalidateUserQuizCache(string $userId): bool
    {
        return $this->invalidateQuizCache(["user:{$userId}"]);
    }

    /**
     * Invalidate category-specific quiz cache.
     */
    public function invalidateCategoryQuizCache(string $categoryId): bool
    {
        return $this->invalidateQuizCache(["category:{$categoryId}"]);
    }

    /**
     * Warm cache with frequently accessed quiz data.
     */
    public function warmCache(array $sessionIds = [], array $questionIds = []): array
    {
        $results = [
            'sessions_warmed' => 0,
            'questions_warmed' => 0,
            'errors' => 0
        ];
        
        // This would typically be called by a cache warming service
        // that knows which data is frequently accessed
        
        $this->logger->info('Quiz cache warming initiated', [
            'sessions_count' => count($sessionIds),
            'questions_count' => count($questionIds)
        ]);
        
        return $results;
    }

    /**
     * Get cache statistics and health metrics.
     */
    public function getCacheStats(): array
    {
        return [
            'session_cache_stats' => $this->getCachePoolStats($this->sessionCache),
            'question_cache_stats' => $this->getCachePoolStats($this->questionCache),
            'metrics' => $this->metricsCollector->getMetrics('quiz'),
        ];
    }

    private function getCachePoolStats(CacheItemPoolInterface $pool): array
    {
        // Basic stats - would need to be implemented based on cache adapter
        return [
            'pool_class' => get_class($pool),
            'adapter_type' => 'redis', // Would be detected dynamically
        ];
    }
}