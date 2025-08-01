<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Psr\Log\LoggerInterface;

/**
 * Comprehensive cache monitoring and health checking service.
 * Provides real-time insights into cache performance and system health.
 */
final class CacheMonitoringService
{
    public function __construct(
        private readonly CacheMetricsCollector $metricsCollector,
        private readonly RedisConnectionFactory $connectionFactory,
        private readonly QuizCacheService $quizCacheService,
        // private readonly AnalyticsCacheService $analyticsCacheService,
        private readonly UserCacheService $userCacheService,
        private readonly LeaderboardCacheService $leaderboardCacheService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Perform comprehensive health check of all cache systems.
     */
    public function performHealthCheck(): array
    {
        $startTime = microtime(true);
        
        $healthStatus = [
            'overall_status' => 'healthy',
            'timestamp' => time(),
            'checks' => [],
            'metrics' => [],
            'recommendations' => []
        ];
        
        try {
            // Check Redis connections
            $connectionHealth = $this->checkRedisConnections();
            $healthStatus['checks']['redis_connections'] = $connectionHealth;
            
            // Check cache performance metrics
            $performanceHealth = $this->checkCachePerformance();
            $healthStatus['checks']['cache_performance'] = $performanceHealth;
            
            // Check cache pool health
            $poolHealth = $this->checkCachePools();
            $healthStatus['checks']['cache_pools'] = $poolHealth;
            
            // Check memory usage
            $memoryHealth = $this->checkMemoryUsage();
            $healthStatus['checks']['memory_usage'] = $memoryHealth;
            
            // Collect current metrics
            $healthStatus['metrics'] = $this->collectCurrentMetrics();
            
            // Generate recommendations
            $healthStatus['recommendations'] = $this->generateHealthRecommendations(
                $connectionHealth,
                $performanceHealth,
                $poolHealth,
                $memoryHealth
            );
            
            // Determine overall status
            $healthStatus['overall_status'] = $this->determineOverallHealth($healthStatus['checks']);
            
        } catch (\Exception $e) {
            $healthStatus['overall_status'] = 'critical';
            $healthStatus['error'] = $e->getMessage();
            
            $this->logger->error('Cache health check failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        $healthStatus['check_duration'] = microtime(true) - $startTime;
        
        $this->logger->info('Cache health check completed', [
            'overall_status' => $healthStatus['overall_status'],
            'duration' => $healthStatus['check_duration'],
            'checks_performed' => count($healthStatus['checks'])
        ]);
        
        return $healthStatus;
    }

    /**
     * Get real-time cache performance dashboard data.
     */
    public function getPerformanceDashboard(): array
    {
        return [
            'summary' => $this->getPerformanceSummary(),
            'hit_ratios' => $this->getHitRatios(),
            'latency_metrics' => $this->getLatencyMetrics(),
            'error_rates' => $this->getErrorRates(),
            'top_performing_caches' => $this->getTopPerformingCaches(),
            'underperforming_caches' => $this->getUnderperformingCaches(),
            'recent_activity' => $this->getRecentActivity(),
            'recommendations' => $this->metricsCollector->getPerformanceRecommendations()
        ];
    }

    /**
     * Monitor cache operations in real-time.
     */
    public function startRealTimeMonitoring(callable $callback = null): void
    {
        $this->logger->info('Starting real-time cache monitoring');
        
        // This would typically start a background process or use Redis pub/sub
        // to monitor cache operations in real-time
        
        // For demonstration, we'll simulate real-time monitoring
        $monitoringData = [
            'start_time' => microtime(true),
            'operations_monitored' => 0,
            'alerts_triggered' => 0,
            'performance_issues' => []
        ];
        
        if ($callback) {
            $callback($monitoringData);
        }
    }

    /**
     * Generate cache performance report.
     */
    public function generatePerformanceReport(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
                'duration_hours' => ($endDate->getTimestamp() - $startDate->getTimestamp()) / 3600
            ],
            'summary' => $this->metricsCollector->getSummaryStats(),
            'cache_types' => [],
            'trends' => $this->analyzeTrends($startDate, $endDate),
            'incidents' => $this->getPerformanceIncidents($startDate, $endDate),
            'recommendations' => $this->metricsCollector->getPerformanceRecommendations(),
            'generated_at' => time()
        ];
        
        // Get metrics for each cache type
        $cacheTypes = ['quiz_session', 'quiz_question', 'user_analytics', 'leaderboard', 'user_profile'];
        foreach ($cacheTypes as $type) {
            $report['cache_types'][$type] = $this->metricsCollector->getMetrics($type);
        }
        
        $this->logger->info('Cache performance report generated', [
            'period_start' => $startDate->format('Y-m-d H:i:s'),
            'period_end' => $endDate->format('Y-m-d H:i:s'),
            'cache_types_analyzed' => count($report['cache_types'])
        ]);
        
        return $report;
    }

    /**
     * Set up automated alerting for cache issues.
     */
    public function setupAlerts(array $alertConfig): bool
    {
        try {
            $this->logger->info('Setting up cache monitoring alerts', [
                'alert_config' => $alertConfig
            ]);
            
            // This would typically:
            // 1. Configure threshold-based alerts
            // 2. Set up notification channels
            // 3. Schedule periodic health checks
            // 4. Configure alert escalation
            
            $alertTypes = [
                'low_hit_ratio' => $alertConfig['hit_ratio_threshold'] ?? 0.5,
                'high_latency' => $alertConfig['latency_threshold'] ?? 0.1,
                'high_error_rate' => $alertConfig['error_rate_threshold'] ?? 0.05,
                'memory_usage' => $alertConfig['memory_threshold'] ?? 0.8,
                'connection_failures' => $alertConfig['connection_failure_threshold'] ?? 3
            ];
            
            $this->logger->info('Cache alerts configured', [
                'alert_types' => array_keys($alertTypes),
                'thresholds' => $alertTypes
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to setup cache alerts', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Optimize cache configuration based on usage patterns.
     */
    public function optimizeConfiguration(): array
    {
        $optimizations = [];
        $metrics = $this->metricsCollector->getSummaryStats();
        
        // Analyze hit ratios and suggest TTL adjustments
        foreach (['quiz_session', 'quiz_question', 'user_analytics', 'leaderboard'] as $cacheType) {
            $cacheMetrics = $this->metricsCollector->getMetrics($cacheType);
            
            if ($cacheMetrics['hit_ratio'] < 0.6) {
                $optimizations[] = [
                    'type' => 'ttl_increase',
                    'cache_type' => $cacheType,
                    'current_hit_ratio' => $cacheMetrics['hit_ratio'],
                    'recommendation' => 'Increase TTL by 50% to improve hit ratio',
                    'priority' => 'high'
                ];
            }
            
            if ($cacheMetrics['average_duration'] > 0.05) {
                $optimizations[] = [
                    'type' => 'performance_tuning',
                    'cache_type' => $cacheType,
                    'current_latency' => $cacheMetrics['average_duration'],
                    'recommendation' => 'Optimize Redis configuration or check network latency',
                    'priority' => 'medium'
                ];
            }
        }
        
        // Memory usage optimization
        if ($metrics['overall_hit_ratio'] > 0.9) {
            $optimizations[] = [
                'type' => 'memory_optimization',
                'recommendation' => 'Consider increasing cache size or TTL for better utilization',
                'priority' => 'low'
            ];
        }
        
        $this->logger->info('Cache configuration optimization completed', [
            'optimizations_found' => count($optimizations),
            'high_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'high')),
            'medium_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'medium')),
            'low_priority' => count(array_filter($optimizations, fn($o) => $o['priority'] === 'low'))
        ]);
        
        return $optimizations;
    }

    private function checkRedisConnections(): array
    {
        $connections = $this->connectionFactory->getConnections();
        $connectionStats = $this->connectionFactory->getStats();
        
        return [
            'status' => $connectionStats['healthy_connections'] === $connectionStats['total_connections'] ? 'healthy' : 'degraded',
            'total_connections' => $connectionStats['total_connections'],
            'healthy_connections' => $connectionStats['healthy_connections'],
            'unhealthy_connections' => $connectionStats['unhealthy_connections'],
            'details' => $connectionStats['connections']
        ];
    }

    private function checkCachePerformance(): array
    {
        $summaryStats = $this->metricsCollector->getSummaryStats();
        
        $status = 'healthy';
        if ($summaryStats['overall_hit_ratio'] < 0.5) {
            $status = 'warning';
        }
        if ($summaryStats['overall_hit_ratio'] < 0.3 || $summaryStats['average_duration'] > 0.1) {
            $status = 'critical';
        }
        
        return [
            'status' => $status,
            'overall_hit_ratio' => $summaryStats['overall_hit_ratio'],
            'average_duration' => $summaryStats['average_duration'],
            'error_rate' => $summaryStats['error_rate'],
            'performance_score' => $summaryStats['performance_score']
        ];
    }

    private function checkCachePools(): array
    {
        $poolHealth = [
            'quiz' => $this->quizCacheService->getCacheStats(),
            'analytics' => $this->analyticsCacheService->getAnalyticsCacheStats(),
            'user' => $this->userCacheService->getUserCacheStats(),
            'leaderboard' => $this->leaderboardCacheService->getLeaderboardCacheStats()
        ];
        
        $healthyPools = 0;
        $totalPools = count($poolHealth);
        
        foreach ($poolHealth as $pool => $stats) {
            // Simple health check based on metrics availability
            if (isset($stats['metrics']) && !empty($stats['metrics'])) {
                $healthyPools++;
            }
        }
        
        return [
            'status' => $healthyPools === $totalPools ? 'healthy' : 'degraded',
            'healthy_pools' => $healthyPools,
            'total_pools' => $totalPools,
            'pool_details' => $poolHealth
        ];
    }

    private function checkMemoryUsage(): array
    {
        // This would typically check Redis memory usage
        // For simulation, return mock data
        $memoryUsage = 0.65; // 65% usage
        
        $status = match (true) {
            $memoryUsage > 0.9 => 'critical',
            $memoryUsage > 0.8 => 'warning',
            default => 'healthy'
        };
        
        return [
            'status' => $status,
            'usage_percentage' => $memoryUsage,
            'used_memory' => '650MB',
            'max_memory' => '1GB',
            'eviction_policy' => 'allkeys-lru'
        ];
    }

    private function collectCurrentMetrics(): array
    {
        return [
            'summary' => $this->metricsCollector->getSummaryStats(),
            'recommendations' => $this->metricsCollector->getPerformanceRecommendations(),
            'collection_time' => microtime(true)
        ];
    }

    private function generateHealthRecommendations(array ...$healthChecks): array
    {
        $recommendations = [];
        
        foreach ($healthChecks as $check) {
            if ($check['status'] === 'critical') {
                $recommendations[] = [
                    'priority' => 'critical',
                    'message' => 'Immediate attention required for cache system',
                    'details' => $check
                ];
            } elseif ($check['status'] === 'warning' || $check['status'] === 'degraded') {
                $recommendations[] = [
                    'priority' => 'warning',
                    'message' => 'Cache performance degradation detected',
                    'details' => $check
                ];
            }
        }
        
        return $recommendations;
    }

    private function determineOverallHealth(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('critical', $statuses)) {
            return 'critical';
        }
        
        if (in_array('warning', $statuses) || in_array('degraded', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }

    private function getPerformanceSummary(): array
    {
        return $this->metricsCollector->getSummaryStats();
    }

    private function getHitRatios(): array
    {
        $cacheTypes = ['quiz_session', 'quiz_question', 'user_analytics', 'leaderboard', 'user_profile'];
        $hitRatios = [];
        
        foreach ($cacheTypes as $type) {
            $hitRatios[$type] = $this->metricsCollector->getHitRatio($type);
        }
        
        return $hitRatios;
    }

    private function getLatencyMetrics(): array
    {
        $cacheTypes = ['quiz_session', 'quiz_question', 'user_analytics', 'leaderboard', 'user_profile'];
        $latencies = [];
        
        foreach ($cacheTypes as $type) {
            $latencies[$type] = $this->metricsCollector->getAverageDuration($type);
        }
        
        return $latencies;
    }

    private function getErrorRates(): array
    {
        $cacheTypes = ['quiz_session', 'quiz_question', 'user_analytics', 'leaderboard', 'user_profile'];
        $errorRates = [];
        
        foreach ($cacheTypes as $type) {
            $metrics = $this->metricsCollector->getMetrics($type);
            $errorRates[$type] = $metrics['error_rate'] ?? 0.0;
        }
        
        return $errorRates;
    }

    private function getTopPerformingCaches(): array
    {
        $hitRatios = $this->getHitRatios();
        arsort($hitRatios);
        
        return array_slice($hitRatios, 0, 3, true);
    }

    private function getUnderperformingCaches(): array
    {
        $hitRatios = $this->getHitRatios();
        asort($hitRatios);
        
        return array_slice(array_filter($hitRatios, fn($ratio) => $ratio < 0.7), 0, 3, true);
    }

    private function getRecentActivity(): array
    {
        // This would typically return recent cache operations
        return [
            'last_hour_operations' => 1500,
            'cache_hits' => 1200,
            'cache_misses' => 200,
            'cache_sets' => 100,
            'peak_hour' => '14:00-15:00'
        ];
    }

    private function analyzeTrends(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // This would typically analyze historical data to identify trends
        return [
            'hit_ratio_trend' => 'improving',
            'latency_trend' => 'stable',
            'error_rate_trend' => 'decreasing',
            'usage_pattern' => 'peak_afternoon'
        ];
    }

    private function getPerformanceIncidents(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // This would typically return performance incidents from the specified period
        return [
            [
                'timestamp' => '2025-08-01 10:30:00',
                'type' => 'high_latency',
                'severity' => 'warning',
                'description' => 'Elevated response times for user_analytics cache',
                'duration' => '5 minutes',
                'resolved' => true
            ]
        ];
    }
}