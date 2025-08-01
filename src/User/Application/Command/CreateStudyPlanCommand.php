<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to create a study plan for a user.
 */
final class CreateStudyPlanCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $description,
        public readonly array $categoryIds,
        public readonly \DateTimeImmutable $targetDate,
        public readonly int $dailyGoalMinutes = 30,
        public readonly string $difficulty = 'medium'
    ) {
    }
}