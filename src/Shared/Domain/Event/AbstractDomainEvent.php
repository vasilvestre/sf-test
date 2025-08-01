<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Abstract base class for domain events.
 * Provides common functionality for all domain events.
 */
abstract class AbstractDomainEvent implements DomainEventInterface
{
    protected \DateTimeImmutable $occurredOn;

    public function __construct(
        private readonly mixed $aggregateId,
        ?\DateTimeImmutable $occurredOn = null
    ) {
        $this->occurredOn = $occurredOn ?? new \DateTimeImmutable();
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAggregateId(): mixed
    {
        return $this->aggregateId;
    }

    public function getEventName(): string
    {
        return static::class;
    }

    public function getEventData(): array
    {
        return [
            'aggregate_id' => $this->aggregateId,
            'occurred_on' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }
}