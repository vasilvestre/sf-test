<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * Interface for query handlers in CQRS architecture.
 * Query handlers execute queries and return read-only data.
 */
interface QueryHandlerInterface
{
    /**
     * Handle the given query and return the result.
     */
    public function handle(QueryInterface $query): mixed;
}