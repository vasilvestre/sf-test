<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Caching service for user-related data.
 * Handles user profiles, preferences, sessions, and personalization data.
 */
final class UserCacheService implements CacheConfigurationInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $userCache,
        private readonly CacheItemPoolInterface $sessionCache,
        private readonly TagAwareCacheInterface $tagAwareCache,
        private readonly CacheMetricsCollector $metricsCollector,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Cache user profile data with role-based TTL.
     */
    public function cacheUserProfile(string $userId, array $profileData): bool
    {
        try {
            $cacheKey = self::USER_PROFILE_PREFIX . $userId;
            $item = $this->userCache->getItem($cacheKey);
            
            // Role-based TTL
            $userRole = $profileData['role'] ?? 'user';
            $ttl = match ($userRole) {
                'admin' => self::USER_PROFILE_TTL / 2, // Admins get fresher data
                'moderator' => self::USER_PROFILE_TTL,
                'user' => self::USER_PROFILE_TTL,
                default => self::USER_PROFILE_TTL
            };
            
            $item->set($profileData);
            $item->expiresAfter($ttl);
            $item->tag([
                self::TAG_USER,
                "user:{$userId}",
                "role:{$userRole}",
                'profile'
            ]);
            
            $success = $this->userCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('user_profile', 'set', $success);
            $this->logger->debug('User profile cached', [
                'user_id' => $userId,
                'role' => $userRole,
                'ttl' => $ttl,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve user profile from cache.
     */
    public function getUserProfile(string $userId): ?array
    {
        try {
            $cacheKey = self::USER_PROFILE_PREFIX . $userId;
            $item = $this->userCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('user_profile', 'hit', true);
                $this->logger->debug('User profile cache hit', ['user_id' => $userId]);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('user_profile', 'miss', true);
            $this->logger->debug('User profile cache miss', ['user_id' => $userId]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user profile from cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache user preferences with personalization optimization.
     */
    public function cacheUserPreferences(string $userId, array $preferences): bool
    {
        try {
            $cacheKey = "user:preferences:{$userId}";
            $item = $this->userCache->getItem($cacheKey);
            
            $item->set($preferences);
            $item->expiresAfter(self::USER_PREFERENCES_TTL);
            $item->tag([
                self::TAG_USER,
                "user:{$userId}",
                'preferences'
            ]);
            
            $success = $this->userCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('user_preferences', 'set', $success);
            $this->logger->debug('User preferences cached', [
                'user_id' => $userId,
                'preferences_count' => count($preferences),
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache user preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve user preferences from cache.
     */
    public function getUserPreferences(string $userId): ?array
    {
        try {
            $cacheKey = "user:preferences:{$userId}";
            $item = $this->userCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('user_preferences', 'hit', true);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('user_preferences', 'miss', true);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user preferences from cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache user session data with activity-based TTL.
     */
    public function cacheUserSession(string $sessionId, string $userId, array $sessionData): bool
    {
        try {
            $cacheKey = self::USER_SESSIONS_PREFIX . $sessionId;
            $item = $this->sessionCache->getItem($cacheKey);
            
            // Activity-based TTL
            $lastActivity = $sessionData['last_activity'] ?? time();
            $timeSinceActivity = time() - $lastActivity;
            
            // More recent activity = longer TTL
            $ttl = match (true) {
                $timeSinceActivity < 300 => self::USER_SESSIONS_TTL, // < 5 min: full TTL
                $timeSinceActivity < 900 => self::USER_SESSIONS_TTL / 2, // < 15 min: half TTL
                default => self::USER_SESSIONS_TTL / 4 // > 15 min: quarter TTL
            };
            
            $item->set($sessionData);
            $item->expiresAfter($ttl);
            $item->tag([
                self::TAG_USER,
                self::TAG_SESSION,
                "user:{$userId}",
                "session:{$sessionId}"
            ]);
            
            $success = $this->sessionCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('user_session', 'set', $success);
            $this->logger->debug('User session cached', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'ttl' => $ttl,
                'time_since_activity' => $timeSinceActivity,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache user session', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve user session from cache.
     */
    public function getUserSession(string $sessionId): ?array
    {
        try {
            $cacheKey = self::USER_SESSIONS_PREFIX . $sessionId;
            $item = $this->sessionCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('user_session', 'hit', true);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('user_session', 'miss', true);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user session from cache', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache user learning progress and achievements.
     */
    public function cacheUserProgress(string $userId, array $progressData): bool
    {
        try {
            $cacheKey = "user:progress:{$userId}";
            $item = $this->userCache->getItem($cacheKey);
            
            $item->set($progressData);
            $item->expiresAfter(self::USER_PROFILE_TTL);
            $item->tag([
                self::TAG_USER,
                "user:{$userId}",
                'progress',
                'achievements'
            ]);
            
            $success = $this->userCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('user_progress', 'set', $success);
            $this->logger->debug('User progress cached', [
                'user_id' => $userId,
                'achievements_count' => count($progressData['achievements'] ?? []),
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache user progress', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve user progress from cache.
     */
    public function getUserProgress(string $userId): ?array
    {
        try {
            $cacheKey = "user:progress:{$userId}";
            $item = $this->userCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('user_progress', 'hit', true);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('user_progress', 'miss', true);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user progress from cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Batch cache multiple user profiles for efficiency.
     */
    public function cacheUserProfilesBatch(array $profiles): array
    {
        $results = [];
        
        foreach ($profiles as $userId => $profileData) {
            $results[$userId] = $this->cacheUserProfile($userId, $profileData);
        }
        
        $successful = count(array_filter($results));
        $this->logger->info('User profiles batch cached', [
            'total' => count($profiles),
            'successful' => $successful,
            'failed' => count($profiles) - $successful
        ]);
        
        return $results;
    }

    /**
     * Retrieve multiple user profiles in batch.
     */
    public function getUserProfilesBatch(array $userIds): array
    {
        $results = [];
        $hits = 0;
        $misses = 0;
        
        foreach ($userIds as $userId) {
            $profile = $this->getUserProfile($userId);
            if ($profile !== null) {
                $results[$userId] = $profile;
                $hits++;
            } else {
                $misses++;
            }
        }
        
        $this->logger->info('User profiles batch retrieved', [
            'requested' => count($userIds),
            'hits' => $hits,
            'misses' => $misses,
            'hit_ratio' => $hits / max(1, count($userIds))
        ]);
        
        return $results;
    }

    /**
     * Invalidate all cache data for a specific user.
     */
    public function invalidateUserCache(string $userId): bool
    {
        try {
            $result = $this->tagAwareCache->invalidateTags(["user:{$userId}"]);
            
            $this->logger->info('User cache invalidated', [
                'user_id' => $userId,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Invalidate user sessions for a specific user.
     */
    public function invalidateUserSessions(string $userId): bool
    {
        try {
            $result = $this->tagAwareCache->invalidateTags(["user:{$userId}", self::TAG_SESSION]);
            
            $this->logger->info('User sessions invalidated', [
                'user_id' => $userId,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate user sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Warm cache with frequently accessed user data.
     */
    public function warmUserCache(array $userIds, array $dataTypes = ['profile', 'preferences', 'progress']): array
    {
        $results = [
            'users_processed' => 0,
            'data_types_warmed' => [],
            'errors' => 0
        ];
        
        // This would typically load data from the database and cache it
        // Implementation would depend on your data access layer
        
        $this->logger->info('User cache warming completed', [
            'user_count' => count($userIds),
            'data_types' => $dataTypes,
            'results' => $results
        ]);
        
        return $results;
    }

    /**
     * Get user cache statistics.
     */
    public function getUserCacheStats(): array
    {
        return [
            'user_cache_stats' => $this->getCachePoolStats($this->userCache),
            'session_cache_stats' => $this->getCachePoolStats($this->sessionCache),
            'metrics' => [
                'user_profile' => $this->metricsCollector->getMetrics('user_profile'),
                'user_preferences' => $this->metricsCollector->getMetrics('user_preferences'),
                'user_session' => $this->metricsCollector->getMetrics('user_session'),
                'user_progress' => $this->metricsCollector->getMetrics('user_progress'),
            ],
        ];
    }

    private function getCachePoolStats(CacheItemPoolInterface $pool): array
    {
        return [
            'pool_class' => get_class($pool),
            'adapter_type' => 'redis',
        ];
    }
}