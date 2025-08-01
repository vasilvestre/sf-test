<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * Middleware for monitoring performance of commands and queries.
 */
final class PerformanceMiddleware implements MiddlewareInterface
{
    private const SLOW_COMMAND_THRESHOLD = 1.0; // 1 second
    private const SLOW_QUERY_THRESHOLD = 0.5;   // 500ms

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $messageClass = get_class($message);
        $busName = $this->getBusName($envelope);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $envelope = $stack->next()->handle($envelope, $stack);

        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true) - $startMemory;

        $this->logPerformanceMetrics($messageClass, $busName, $executionTime, $memoryUsage);

        return $envelope;
    }

    private function getBusName(Envelope $envelope): string
    {
        $busNameStamp = $envelope->last(BusNameStamp::class);
        return $busNameStamp ? $busNameStamp->getBusName() : 'unknown';
    }

    private function logPerformanceMetrics(
        string $messageClass,
        string $busName,
        float $executionTime,
        int $memoryUsage
    ): void {
        $context = [
            'message_class' => $messageClass,
            'bus' => $busName,
            'execution_time' => round($executionTime, 4),
            'memory_usage' => $this->formatBytes($memoryUsage),
        ];

        $threshold = $busName === 'command.bus' ? self::SLOW_COMMAND_THRESHOLD : self::SLOW_QUERY_THRESHOLD;

        if ($executionTime > $threshold) {
            $this->logger->warning("Slow {$busName} detected: {$messageClass}", $context);
        } else {
            $this->logger->debug("Performance metrics for {$busName}: {$messageClass}", $context);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }
        
        return round($bytes, 2) . ' ' . $units[$index];
    }
}