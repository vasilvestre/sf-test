<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Interface for domain events.
 * Domain events represent something important that happened in the domain.
 */
interface DomainEventInterface
{
    /**
     * Get the time when this event occurred.
     */
    public function getOccurredOn(): \DateTimeImmutable;

    /**
     * Get the aggregate ID that this event is related to.
     */
    public function getAggregateId(): mixed;

    /**
     * Get the name of this event.
     */
    public function getEventName(): string;

    /**
     * Get the event data as an array.
     */
    public function getEventData(): array;
}