<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to create a new quiz category.
 */
final class CreateCategoryCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null
    ) {
    }
}