<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

use App\Shared\Domain\Event\DomainEventInterface;

/**
 * Base class for aggregate roots in the domain layer.
 * Handles domain event recording and identity management.
 */
abstract class AggregateRoot
{
    /** @var DomainEventInterface[] */
    private array $domainEvents = [];

    /**
     * Record a domain event that will be dispatched later.
     */
    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Get all recorded domain events.
     *
     * @return DomainEventInterface[]
     */
    public function getRecordedEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Get all uncommitted domain events.
     *
     * @return DomainEventInterface[]
     */
    public function getUncommittedEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Mark all events as committed and clear them.
     */
    public function markEventsAsCommitted(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Clear all recorded domain events.
     */
    public function clearRecordedEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Get the unique identifier of this aggregate.
     */
    abstract public function getId(): mixed;

    /**
     * Check if this aggregate is equal to another.
     */
    public function equals(AggregateRoot $other): bool
    {
        return $this->getId() === $other->getId() && get_class($this) === get_class($other);
    }
}