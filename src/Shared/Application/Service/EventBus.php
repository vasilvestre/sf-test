<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

use App\Shared\Domain\Event\DomainEventInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Event bus facade for CQRS.
 */
final class EventBus
{
    public function __construct(
        private readonly MessageBusInterface $eventBus
    ) {
    }

    /**
     * Publish a domain event.
     */
    public function publish(DomainEventInterface $event): void
    {
        $this->eventBus->dispatch($event);
    }

    /**
     * Publish multiple domain events.
     */
    public function publishMultiple(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
}