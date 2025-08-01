<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repository;

/**
 * Interface for domain repositories.
 * Repositories provide collection-like access to domain objects.
 */
interface RepositoryInterface
{
    /**
     * Get the next identity for new aggregates.
     */
    public function nextIdentity(): mixed;
}