<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Base exception for all domain-related exceptions.
 */
abstract class DomainException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}