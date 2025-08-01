<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Exception thrown when attempting to verify an already verified user.
 */
final class UserAlreadyVerifiedException extends DomainException
{
}