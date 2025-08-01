<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

use App\Shared\Application\Query\QueryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Query bus facade for CQRS.
 */
final class QueryBus
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    /**
     * Ask a query and return the result.
     */
    public function ask(QueryInterface $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        
        $handledStamp = $envelope->last(HandledStamp::class);
        
        return $handledStamp?->getResult();
    }
}