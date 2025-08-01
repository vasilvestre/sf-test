<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when a user is not verified for an action that requires verification.
 */
final class UserNotVerifiedException extends DomainException
{
}