<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating Redis connections with advanced configuration.
 * Supports clustering, sentinel, and connection pooling.
 */
final class RedisConnectionFactory
{
    private array $connections = [];
    private array $healthChecks = [];

    public function __construct(
        private readonly string $host = 'localhost',
        private readonly int $port = 6379,
        private readonly ?string $password = null,
        private readonly bool $clusterEnabled = false,
        private readonly bool $sentinelEnabled = false,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Create a Redis connection for a specific database.
     */
    public function createConnection(int $database = 0, string $alias = 'default'): Client
    {
        $connectionKey = "{$alias}:{$database}";
        
        if (isset($this->connections[$connectionKey])) {
            // Perform health check before returning cached connection
            if ($this->isConnectionHealthy($connectionKey)) {
                return $this->connections[$connectionKey];
            } else {
                // Remove unhealthy connection
                unset($this->connections[$connectionKey]);
            }
        }

        $connection = $this->buildConnection($database, $alias);
        $this->connections[$connectionKey] = $connection;
        
        // Perform initial health check
        $this->performHealthCheck($connectionKey, $connection);
        
        return $connection;
    }

    /**
     * Get all active connections.
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Close all connections.
     */
    public function closeConnections(): void
    {
        foreach ($this->connections as $connection) {
            try {
                $connection->disconnect();
            } catch (\Exception $e) {
                $this->logger?->warning('Failed to close Redis connection', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->connections = [];
        $this->healthChecks = [];
    }

    /**
     * Get connection statistics.
     */
    public function getStats(): array
    {
        $stats = [
            'total_connections' => count($this->connections),
            'healthy_connections' => 0,
            'unhealthy_connections' => 0,
            'connections' => []
        ];

        foreach ($this->connections as $key => $connection) {
            $isHealthy = $this->isConnectionHealthy($key);
            
            if ($isHealthy) {
                $stats['healthy_connections']++;
            } else {
                $stats['unhealthy_connections']++;
            }

            $stats['connections'][$key] = [
                'healthy' => $isHealthy,
                'last_check' => $this->healthChecks[$key]['last_check'] ?? null,
                'checks_passed' => $this->healthChecks[$key]['checks_passed'] ?? 0,
                'checks_failed' => $this->healthChecks[$key]['checks_failed'] ?? 0,
            ];
        }

        return $stats;
    }

    private function buildConnection(int $database, string $alias): Client
    {
        $parameters = [
            'scheme' => 'tcp',
            'host' => $this->host,
            'port' => $this->port,
            'database' => $database,
        ];

        if ($this->password) {
            $parameters['password'] = $this->password;
        }

        $options = [
            'prefix' => "quiz_app:{$alias}:",
            'profile' => '7.0',
            'exceptions' => true,
            'connections' => [
                'tcp' => [
                    'timeout' => 5.0,
                    'read_write_timeout' => 0,
                    'tcp_nodelay' => true,
                    'persistent' => true,
                ]
            ]
        ];

        if ($this->clusterEnabled) {
            $options['cluster'] = 'redis';
            $parameters = [
                ['host' => $this->host, 'port' => $this->port, 'database' => $database],
                // Add more cluster nodes here
            ];
        }

        if ($this->sentinelEnabled) {
            $options['replication'] = 'sentinel';
            $options['service'] = 'quiz_app';
        }

        try {
            $connection = new Client($parameters, $options);
            
            // Test the connection
            $connection->ping();
            
            $this->logger?->info('Redis connection established', [
                'alias' => $alias,
                'database' => $database,
                'host' => $this->host,
                'port' => $this->port
            ]);
            
            return $connection;
        } catch (\Exception $e) {
            $this->logger?->error('Failed to create Redis connection', [
                'alias' => $alias,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException(
                "Failed to create Redis connection for '{$alias}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function isConnectionHealthy(string $connectionKey): bool
    {
        if (!isset($this->connections[$connectionKey])) {
            return false;
        }

        $lastCheck = $this->healthChecks[$connectionKey]['last_check'] ?? 0;
        $checkInterval = 30; // Check every 30 seconds
        
        if (time() - $lastCheck < $checkInterval) {
            return $this->healthChecks[$connectionKey]['healthy'] ?? false;
        }

        return $this->performHealthCheck($connectionKey, $this->connections[$connectionKey]);
    }

    private function performHealthCheck(string $connectionKey, Client $connection): bool
    {
        try {
            $start = microtime(true);
            $result = $connection->ping();
            $latency = (microtime(true) - $start) * 1000; // Convert to milliseconds
            
            $isHealthy = $result === 'PONG' && $latency < 100; // Healthy if latency < 100ms
            
            $this->healthChecks[$connectionKey] = [
                'healthy' => $isHealthy,
                'last_check' => time(),
                'latency' => $latency,
                'checks_passed' => ($this->healthChecks[$connectionKey]['checks_passed'] ?? 0) + ($isHealthy ? 1 : 0),
                'checks_failed' => ($this->healthChecks[$connectionKey]['checks_failed'] ?? 0) + ($isHealthy ? 0 : 1),
            ];
            
            if (!$isHealthy) {
                $this->logger?->warning('Redis connection health check failed', [
                    'connection' => $connectionKey,
                    'latency' => $latency,
                    'result' => $result
                ]);
            }
            
            return $isHealthy;
        } catch (\Exception $e) {
            $this->healthChecks[$connectionKey] = [
                'healthy' => false,
                'last_check' => time(),
                'error' => $e->getMessage(),
                'checks_failed' => ($this->healthChecks[$connectionKey]['checks_failed'] ?? 0) + 1,
            ];
            
            $this->logger?->error('Redis connection health check exception', [
                'connection' => $connectionKey,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}