<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * Interface for command handlers in CQRS architecture.
 * Command handlers execute commands and modify system state.
 */
interface CommandHandlerInterface
{
    /**
     * Handle the given command.
     */
    public function handle(CommandInterface $command): mixed;
}