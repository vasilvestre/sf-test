<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Log\LoggerInterface;

/**
 * Collects and analyzes cache performance metrics.
 * Provides insights into cache hit ratios, latency, and optimization opportunities.
 */
final class CacheMetricsCollector
{
    private array $metrics = [];
    private array $operationTimes = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Record a cache operation for metrics collection.
     */
    public function recordCacheOperation(string $type, string $operation, bool $success, float $duration = null): void
    {
        $timestamp = microtime(true);
        
        if (!isset($this->metrics[$type])) {
            $this->metrics[$type] = [
                'operations' => [],
                'hit_count' => 0,
                'miss_count' => 0,
                'set_count' => 0,
                'delete_count' => 0,
                'error_count' => 0,
                'total_duration' => 0.0,
                'operation_count' => 0,
            ];
        }

        $this->metrics[$type]['operations'][] = [
            'operation' => $operation,
            'success' => $success,
            'timestamp' => $timestamp,
            'duration' => $duration
        ];

        // Update counters
        if ($success) {
            switch ($operation) {
                case 'hit':
                    $this->metrics[$type]['hit_count']++;
                    break;
                case 'miss':
                    $this->metrics[$type]['miss_count']++;
                    break;
                case 'set':
                    $this->metrics[$type]['set_count']++;
                    break;
                case 'delete':
                    $this->metrics[$type]['delete_count']++;
                    break;
            }
        } else {
            $this->metrics[$type]['error_count']++;
        }

        if ($duration !== null) {
            $this->metrics[$type]['total_duration'] += $duration;
        }

        $this->metrics[$type]['operation_count']++;

        // Log performance issues
        if ($duration !== null && $duration > 0.1) { // > 100ms
            $this->logger->warning('Slow cache operation detected', [
                'type' => $type,
                'operation' => $operation,
                'duration' => $duration,
                'success' => $success
            ]);
        }
    }

    /**
     * Get metrics for a specific cache type or all types.
     */
    public function getMetrics(string $type = null): array
    {
        if ($type !== null) {
            return $this->calculateMetrics($type);
        }

        $allMetrics = [];
        foreach (array_keys($this->metrics) as $cacheType) {
            $allMetrics[$cacheType] = $this->calculateMetrics($cacheType);
        }

        return $allMetrics;
    }

    /**
     * Get cache hit ratio for a specific type.
     */
    public function getHitRatio(string $type): float
    {
        if (!isset($this->metrics[$type])) {
            return 0.0;
        }

        $hits = $this->metrics[$type]['hit_count'];
        $misses = $this->metrics[$type]['miss_count'];
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) : 0.0;
    }

    /**
     * Get average operation duration for a specific type.
     */
    public function getAverageDuration(string $type): float
    {
        if (!isset($this->metrics[$type]) || $this->metrics[$type]['operation_count'] === 0) {
            return 0.0;
        }

        return $this->metrics[$type]['total_duration'] / $this->metrics[$type]['operation_count'];
    }

    /**
     * Get performance recommendations based on collected metrics.
     */
    public function getPerformanceRecommendations(): array
    {
        $recommendations = [];

        foreach ($this->metrics as $type => $metrics) {
            $hitRatio = $this->getHitRatio($type);
            $avgDuration = $this->getAverageDuration($type);
            $errorRate = $metrics['operation_count'] > 0 
                ? ($metrics['error_count'] / $metrics['operation_count']) 
                : 0;

            // Low hit ratio recommendations
            if ($hitRatio < 0.5) {
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'hit_ratio',
                    'severity' => 'high',
                    'message' => sprintf("Low cache hit ratio (%.1f%%) for %s. Consider increasing TTL or warming cache.", $hitRatio * 100, $type),
                    'current_value' => $hitRatio,
                    'recommended_value' => 0.8
                ];
            } elseif ($hitRatio < 0.8) {
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'hit_ratio',
                    'severity' => 'medium',
                    'message' => sprintf("Moderate cache hit ratio (%.1f%%) for %s. Room for optimization.", $hitRatio * 100, $type),
                    'current_value' => $hitRatio,
                    'recommended_value' => 0.8
                ];
            }

            // High latency recommendations
            if ($avgDuration > 0.05) { // > 50ms
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'latency',
                    'severity' => 'high',
                    'message' => sprintf("High average latency (%.0fms) for %s. Check Redis connection and network.", $avgDuration * 1000, $type),
                    'current_value' => $avgDuration,
                    'recommended_value' => 0.01
                ];
            } elseif ($avgDuration > 0.02) { // > 20ms
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'latency',
                    'severity' => 'medium',
                    'message' => sprintf("Elevated latency (%.0fms) for %s. Monitor performance.", $avgDuration * 1000, $type),
                    'current_value' => $avgDuration,
                    'recommended_value' => 0.01
                ];
            }

            // High error rate recommendations
            if ($errorRate > 0.05) { // > 5% error rate
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'reliability',
                    'severity' => 'high',
                    'message' => sprintf("High error rate (%.1f%%) for %s. Check Redis connectivity and health.", $errorRate * 100, $type),
                    'current_value' => $errorRate,
                    'recommended_value' => 0.01
                ];
            }

            // TTL optimization recommendations
            $setToHitRatio = $metrics['hit_count'] > 0 
                ? ($metrics['set_count'] / $metrics['hit_count']) 
                : 0;
            
            if ($setToHitRatio > 0.5) {
                $recommendations[] = [
                    'type' => $type,
                    'category' => 'ttl_optimization',
                    'severity' => 'medium',
                    'message' => sprintf("High set-to-hit ratio (%.2f) for %s. Consider increasing TTL.", $setToHitRatio, $type),
                    'current_value' => $setToHitRatio,
                    'recommended_value' => 0.2
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Reset metrics for a specific type or all types.
     */
    public function resetMetrics(string $type = null): void
    {
        if ($type !== null) {
            unset($this->metrics[$type]);
        } else {
            $this->metrics = [];
        }

        $this->logger->info('Cache metrics reset', ['type' => $type ?? 'all']);
    }

    /**
     * Get summary statistics across all cache types.
     */
    public function getSummaryStats(): array
    {
        $totalOperations = 0;
        $totalHits = 0;
        $totalMisses = 0;
        $totalErrors = 0;
        $totalDuration = 0.0;
        $cacheTypes = count($this->metrics);

        foreach ($this->metrics as $metrics) {
            $totalOperations += $metrics['operation_count'];
            $totalHits += $metrics['hit_count'];
            $totalMisses += $metrics['miss_count'];
            $totalErrors += $metrics['error_count'];
            $totalDuration += $metrics['total_duration'];
        }

        $overallHitRatio = ($totalHits + $totalMisses) > 0 
            ? ($totalHits / ($totalHits + $totalMisses))
            : 0.0;

        $avgDuration = $totalOperations > 0 
            ? ($totalDuration / $totalOperations)
            : 0.0;

        $errorRate = $totalOperations > 0 
            ? ($totalErrors / $totalOperations)
            : 0.0;

        return [
            'cache_types' => $cacheTypes,
            'total_operations' => $totalOperations,
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'total_errors' => $totalErrors,
            'overall_hit_ratio' => $overallHitRatio,
            'average_duration' => $avgDuration,
            'error_rate' => $errorRate,
            'performance_score' => $this->calculatePerformanceScore($overallHitRatio, $avgDuration, $errorRate)
        ];
    }

    private function calculateMetrics(string $type): array
    {
        if (!isset($this->metrics[$type])) {
            return [
                'hit_ratio' => 0.0,
                'average_duration' => 0.0,
                'error_rate' => 0.0,
                'operation_count' => 0,
                'recommendations' => []
            ];
        }

        $metrics = $this->metrics[$type];
        $hitRatio = $this->getHitRatio($type);
        $avgDuration = $this->getAverageDuration($type);
        $errorRate = $metrics['operation_count'] > 0 
            ? ($metrics['error_count'] / $metrics['operation_count'])
            : 0.0;

        return [
            'hit_count' => $metrics['hit_count'],
            'miss_count' => $metrics['miss_count'],
            'set_count' => $metrics['set_count'],
            'delete_count' => $metrics['delete_count'],
            'error_count' => $metrics['error_count'],
            'operation_count' => $metrics['operation_count'],
            'hit_ratio' => $hitRatio,
            'average_duration' => $avgDuration,
            'error_rate' => $errorRate,
            'total_duration' => $metrics['total_duration'],
            'performance_score' => $this->calculatePerformanceScore($hitRatio, $avgDuration, $errorRate)
        ];
    }

    private function calculatePerformanceScore(float $hitRatio, float $avgDuration, float $errorRate): float
    {
        // Performance score from 0-100
        $hitRatioScore = $hitRatio * 50; // Max 50 points for hit ratio
        $latencyScore = max(0, 30 - ($avgDuration * 1000)); // Max 30 points for latency (penalty for > 30ms)
        $reliabilityScore = max(0, 20 - ($errorRate * 400)); // Max 20 points for reliability

        return min(100, $hitRatioScore + $latencyScore + $reliabilityScore);
    }
}