<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Specialized caching service for leaderboard and competitive data.
 * Handles rankings, competitive metrics, and real-time competition updates.
 */
final class LeaderboardCacheService implements CacheConfigurationInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $leaderboardCache,
        private readonly CacheItemPoolInterface $realtimeCache,
        private readonly TagAwareCacheInterface $tagAwareCache,
        private readonly CacheMetricsCollector $metricsCollector,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Cache leaderboard data with competition-aware TTL.
     */
    public function cacheLeaderboard(
        string $leaderboardType,
        array $leaderboardData,
        array $context = []
    ): bool {
        try {
            $cacheKey = self::LEADERBOARD_PREFIX . $leaderboardType;
            $item = $this->leaderboardCache->getItem($cacheKey);
            
            // Competition-aware TTL
            $isLiveCompetition = $context['live_competition'] ?? false;
            $participantCount = count($leaderboardData);
            $lastUpdate = $context['last_update'] ?? time();
            
            $ttl = $this->calculateLeaderboardTTL(
                $isLiveCompetition,
                $participantCount,
                $lastUpdate
            );
            
            $enrichedData = [
                'entries' => $leaderboardData,
                'metadata' => [
                    'type' => $leaderboardType,
                    'participant_count' => $participantCount,
                    'last_update' => $lastUpdate,
                    'is_live' => $isLiveCompetition,
                    'cached_at' => time(),
                ],
                'context' => $context
            ];
            
            $item->set($enrichedData);
            $item->expiresAfter($ttl);
            $item->tag([
                self::TAG_ANALYTICS,
                self::TAG_LEADERBOARD,
                "type:{$leaderboardType}",
                $isLiveCompetition ? 'live' : 'static',
                "participants:{$this->getParticipantCountBucket($participantCount)}"
            ]);
            
            $success = $this->leaderboardCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('leaderboard', 'set', $success);
            $this->logger->debug('Leaderboard cached', [
                'type' => $leaderboardType,
                'participant_count' => $participantCount,
                'ttl' => $ttl,
                'is_live' => $isLiveCompetition,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache leaderboard', [
                'type' => $leaderboardType,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve leaderboard from cache with position filtering.
     */
    public function getLeaderboard(
        string $leaderboardType,
        int $startPosition = 1,
        int $limit = 50
    ): ?array {
        try {
            $cacheKey = self::LEADERBOARD_PREFIX . $leaderboardType;
            $item = $this->leaderboardCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $data = $item->get();
                
                // Apply position filtering
                if ($startPosition > 1 || $limit < count($data['entries'])) {
                    $data['entries'] = array_slice(
                        $data['entries'],
                        $startPosition - 1,
                        $limit
                    );
                    
                    $data['metadata']['filtered'] = true;
                    $data['metadata']['start_position'] = $startPosition;
                    $data['metadata']['limit'] = $limit;
                }
                
                $this->metricsCollector->recordCacheOperation('leaderboard', 'hit', true);
                $this->logger->debug('Leaderboard cache hit', [
                    'type' => $leaderboardType,
                    'start_position' => $startPosition,
                    'limit' => $limit
                ]);
                
                return $data;
            }
            
            $this->metricsCollector->recordCacheOperation('leaderboard', 'miss', true);
            $this->logger->debug('Leaderboard cache miss', ['type' => $leaderboardType]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve leaderboard from cache', [
                'type' => $leaderboardType,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache user's position in specific leaderboards.
     */
    public function cacheUserPosition(string $userId, string $leaderboardType, int $position, array $details = []): bool
    {
        try {
            $cacheKey = "user_position:{$leaderboardType}:{$userId}";
            $item = $this->leaderboardCache->getItem($cacheKey);
            
            $positionData = [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'position' => $position,
                'details' => $details,
                'cached_at' => time(),
            ];
            
            $item->set($positionData);
            $item->expiresAfter(self::LEADERBOARD_TTL);
            $item->tag([
                self::TAG_LEADERBOARD,
                "user:{$userId}",
                "type:{$leaderboardType}",
                'position'
            ]);
            
            $success = $this->leaderboardCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('user_position', 'set', $success);
            $this->logger->debug('User position cached', [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'position' => $position,
                'success' => $success
            ]);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache user position', [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve user's position from cache.
     */
    public function getUserPosition(string $userId, string $leaderboardType): ?array
    {
        try {
            $cacheKey = "user_position:{$leaderboardType}:{$userId}";
            $item = $this->leaderboardCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('user_position', 'hit', true);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('user_position', 'miss', true);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user position from cache', [
                'user_id' => $userId,
                'leaderboard_type' => $leaderboardType,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache real-time competitive metrics.
     */
    public function cacheCompetitiveMetrics(string $metricsKey, array $metricsData): bool
    {
        try {
            $cacheKey = "competitive:metrics:{$metricsKey}";
            $item = $this->realtimeCache->getItem($cacheKey);
            
            $enrichedMetrics = [
                'data' => $metricsData,
                'timestamp' => microtime(true),
                'key' => $metricsKey,
            ];
            
            $item->set($enrichedMetrics);
            $item->expiresAfter(self::REALTIME_METRICS_TTL);
            $item->tag([
                self::TAG_ANALYTICS,
                self::TAG_REALTIME,
                self::TAG_LEADERBOARD,
                "metrics:{$metricsKey}"
            ]);
            
            $success = $this->realtimeCache->save($item);
            
            $this->metricsCollector->recordCacheOperation('competitive_metrics', 'set', $success);
            
            return $success;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache competitive metrics', [
                'metrics_key' => $metricsKey,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve real-time competitive metrics.
     */
    public function getCompetitiveMetrics(string $metricsKey): ?array
    {
        try {
            $cacheKey = "competitive:metrics:{$metricsKey}";
            $item = $this->realtimeCache->getItem($cacheKey);
            
            if ($item->isHit()) {
                $this->metricsCollector->recordCacheOperation('competitive_metrics', 'hit', true);
                return $item->get();
            }
            
            $this->metricsCollector->recordCacheOperation('competitive_metrics', 'miss', true);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve competitive metrics from cache', [
                'metrics_key' => $metricsKey,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Cache multiple leaderboards in batch for tournament scenarios.
     */
    public function cacheLeaderboardsBatch(array $leaderboards): array
    {
        $results = [];
        
        foreach ($leaderboards as $type => $data) {
            $context = $data['context'] ?? [];
            $entries = $data['entries'] ?? [];
            
            $results[$type] = $this->cacheLeaderboard($type, $entries, $context);
        }
        
        $successful = count(array_filter($results));
        $this->logger->info('Leaderboards batch cached', [
            'total' => count($leaderboards),
            'successful' => $successful,
            'failed' => count($leaderboards) - $successful
        ]);
        
        return $results;
    }

    /**
     * Retrieve multiple leaderboards for tournament display.
     */
    public function getLeaderboardsBatch(array $leaderboardTypes, int $limit = 10): array
    {
        $results = [];
        $hits = 0;
        $misses = 0;
        
        foreach ($leaderboardTypes as $type) {
            $leaderboard = $this->getLeaderboard($type, 1, $limit);
            if ($leaderboard !== null) {
                $results[$type] = $leaderboard;
                $hits++;
            } else {
                $misses++;
            }
        }
        
        $this->logger->info('Leaderboards batch retrieved', [
            'requested' => count($leaderboardTypes),
            'hits' => $hits,
            'misses' => $misses,
            'hit_ratio' => $hits / max(1, count($leaderboardTypes))
        ]);
        
        return $results;
    }

    /**
     * Invalidate leaderboard cache by type or criteria.
     */
    public function invalidateLeaderboardCache(array $criteria = []): bool
    {
        try {
            $tags = [self::TAG_LEADERBOARD];
            
            if (isset($criteria['type'])) {
                $tags[] = "type:{$criteria['type']}";
            }
            
            if (isset($criteria['user_id'])) {
                $tags[] = "user:{$criteria['user_id']}";
            }
            
            if (isset($criteria['live_only']) && $criteria['live_only']) {
                $tags[] = 'live';
            }
            
            $result = $this->tagAwareCache->invalidateTags($tags);
            
            $this->logger->info('Leaderboard cache invalidated', [
                'criteria' => $criteria,
                'tags' => $tags,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate leaderboard cache', [
                'criteria' => $criteria,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Update user's position without full leaderboard refresh.
     */
    public function updateUserPosition(string $userId, string $leaderboardType, int $newPosition, array $details = []): bool
    {
        // Update individual position cache
        $positionUpdated = $this->cacheUserPosition($userId, $leaderboardType, $newPosition, $details);
        
        // Invalidate full leaderboard cache to ensure consistency
        $leaderboardInvalidated = $this->invalidateLeaderboardCache(['type' => $leaderboardType]);
        
        $this->logger->info('User position updated', [
            'user_id' => $userId,
            'leaderboard_type' => $leaderboardType,
            'new_position' => $newPosition,
            'position_cached' => $positionUpdated,
            'leaderboard_invalidated' => $leaderboardInvalidated
        ]);
        
        return $positionUpdated && $leaderboardInvalidated;
    }

    /**
     * Get leaderboard cache statistics and health metrics.
     */
    public function getLeaderboardCacheStats(): array
    {
        return [
            'leaderboard_cache_stats' => $this->getCachePoolStats($this->leaderboardCache),
            'realtime_cache_stats' => $this->getCachePoolStats($this->realtimeCache),
            'metrics' => [
                'leaderboard' => $this->metricsCollector->getMetrics('leaderboard'),
                'user_position' => $this->metricsCollector->getMetrics('user_position'),
                'competitive_metrics' => $this->metricsCollector->getMetrics('competitive_metrics'),
            ],
        ];
    }

    private function calculateLeaderboardTTL(bool $isLive, int $participantCount, int $lastUpdate): int
    {
        $baseTTL = self::LEADERBOARD_TTL;
        
        // Live competitions need fresher data
        if ($isLive) {
            $baseTTL = min($baseTTL, 60); // Max 1 minute for live
        }
        
        // More participants = more dynamic = shorter TTL
        if ($participantCount > 1000) {
            $baseTTL = (int) ($baseTTL * 0.5);
        } elseif ($participantCount > 100) {
            $baseTTL = (int) ($baseTTL * 0.75);
        }
        
        // Recent updates = shorter TTL
        $timeSinceUpdate = time() - $lastUpdate;
        if ($timeSinceUpdate < 60) { // Updated in last minute
            $baseTTL = (int) ($baseTTL * 0.5);
        }
        
        return max(30, $baseTTL); // Minimum 30 seconds
    }

    private function getParticipantCountBucket(int $count): string
    {
        return match (true) {
            $count < 10 => 'small',
            $count < 100 => 'medium',
            $count < 1000 => 'large',
            default => 'xlarge'
        };
    }

    private function getCachePoolStats(CacheItemPoolInterface $pool): array
    {
        return [
            'pool_class' => get_class($pool),
            'adapter_type' => 'redis',
        ];
    }
}