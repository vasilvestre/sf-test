<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to complete a quiz session and calculate final results.
 * Triggers analytics calculations and learning recommendations.
 */
final readonly class CompleteQuizSessionCommand implements CommandInterface
{
    public function __construct(
        public string $sessionId,
        public float $totalTimeSpent
    ) {
    }
}