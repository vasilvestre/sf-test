<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Middleware for managing database transactions in commands.
 */
final class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->entityManager->beginTransaction();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
            $this->entityManager->commit();
            
            return $envelope;
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }
}