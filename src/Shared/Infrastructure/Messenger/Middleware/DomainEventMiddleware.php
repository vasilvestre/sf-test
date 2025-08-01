<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use App\Shared\Domain\Entity\AggregateRoot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Middleware for dispatching domain events after command handling.
 */
final class DomainEventMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        // Only dispatch events after successful command handling
        if ($envelope->last(HandledStamp::class)) {
            $this->dispatchDomainEvents();
        }

        return $envelope;
    }

    private function dispatchDomainEvents(): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $entities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityDeletions()
        );

        $events = [];
        foreach ($entities as $entity) {
            if ($entity instanceof AggregateRoot) {
                $events = array_merge($events, $entity->getUncommittedEvents());
                $entity->markEventsAsCommitted();
            }
        }

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}