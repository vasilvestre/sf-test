<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Messenger\Middleware;

use App\Shared\Infrastructure\Messenger\Middleware\PerformanceMiddleware;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for PerformanceMiddleware.
 */
final class PerformanceMiddlewareTest extends TestCase
{
    private LoggerInterface|MockObject $logger;
    private PerformanceMiddleware $middleware;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->middleware = new PerformanceMiddleware($this->logger);
    }

    public function testHandleLogsPerformanceMetrics(): void
    {
        // Arrange
        $message = new class {};
        $envelope = new Envelope($message, [new BusNameStamp('command.bus')]);
        
        $stack = $this->createMock(StackInterface::class);
        $stack->expects($this->once())
            ->method('next')
            ->willReturn($stack);
        
        $stack->expects($this->once())
            ->method('handle')
            ->with($envelope, $stack)
            ->willReturn($envelope);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Performance metrics for command.bus'),
                $this->arrayHasKey('execution_time')
            );

        // Act
        $result = $this->middleware->handle($envelope, $stack);

        // Assert
        $this->assertSame($envelope, $result);
    }

    public function testHandleLogsSlowCommandWarning(): void
    {
        // Arrange
        $message = new class {};
        $envelope = new Envelope($message, [new BusNameStamp('command.bus')]);
        
        $stack = $this->createMock(StackInterface::class);
        $stack->expects($this->once())
            ->method('next')
            ->willReturn($stack);
        
        $stack->expects($this->once())
            ->method('handle')
            ->with($envelope, $stack)
            ->will($this->returnCallback(function() use ($envelope) {
                // Simulate slow execution
                usleep(1100000); // 1.1 seconds
                return $envelope;
            }));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Slow command.bus detected'),
                $this->arrayHasKey('execution_time')
            );

        // Act
        $result = $this->middleware->handle($envelope, $stack);

        // Assert
        $this->assertSame($envelope, $result);
    }
}