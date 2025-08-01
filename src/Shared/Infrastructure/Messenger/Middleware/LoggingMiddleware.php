<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Middleware for logging command and query execution.
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $messageClass = get_class($message);
        $busName = $this->getBusName($envelope);

        $context = [
            'message_class' => $messageClass,
            'bus' => $busName,
            'message_id' => $this->getMessageId($envelope),
        ];

        $this->logger->info("Handling {$busName} message: {$messageClass}", $context);

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
            
            $executionTime = microtime(true) - $startTime;
            $context['execution_time'] = $executionTime;
            
            $this->logger->info("Successfully handled {$busName} message: {$messageClass}", $context);
            
            return $envelope;
        } catch (\Throwable $exception) {
            $executionTime = microtime(true) - $startTime;
            $context['execution_time'] = $executionTime;
            $context['error'] = $exception->getMessage();
            $context['error_class'] = get_class($exception);
            
            $this->logger->error("Failed to handle {$busName} message: {$messageClass}", $context);
            
            throw $exception;
        }
    }

    private function getBusName(Envelope $envelope): string
    {
        $busNameStamp = $envelope->last(BusNameStamp::class);
        return $busNameStamp ? $busNameStamp->getBusName() : 'unknown';
    }

    private function getMessageId(Envelope $envelope): string
    {
        return spl_object_hash($envelope->getMessage());
    }
}