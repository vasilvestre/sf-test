<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

use App\Shared\Application\Command\CommandInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Command bus facade for CQRS.
 */
final class CommandBus
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * Dispatch a command and return the result.
     */
    public function dispatch(CommandInterface $command): mixed
    {
        $envelope = $this->commandBus->dispatch($command);
        
        $handledStamp = $envelope->last(HandledStamp::class);
        
        return $handledStamp?->getResult();
    }

    /**
     * Dispatch a command without waiting for result (fire and forget).
     */
    public function dispatchAsync(CommandInterface $command): void
    {
        $this->commandBus->dispatch($command);
    }
}