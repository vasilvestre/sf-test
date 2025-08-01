<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Command\CommandInterface;

/**
 * Command to record user achievement.
 */
final class RecordAchievementCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $achievementType,
        public readonly array $metadata = [],
        public readonly \DateTimeImmutable $earnedAt = new \DateTimeImmutable()
    ) {
    }
}