<?php

declare(strict_types=1);

namespace App\Quiz\Application\Command;

use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\TimeLimit;
use App\Shared\Application\Command\CommandInterface;
use App\User\Domain\Entity\UserId;

/**
 * Command to start a new quiz session.
 * Supports adaptive learning and personalized quiz generation.
 */
final readonly class StartQuizSessionCommand implements CommandInterface
{
    public function __construct(
        public UserId $userId,
        public ?string $categoryId = null,
        public ?EnhancedDifficultyLevel $targetDifficulty = null,
        public int $questionCount = 15,
        public ?TimeLimit $timeLimit = null,
        public bool $adaptiveLearning = true,
        public array $questionTypes = [],
        public array $tags = [],
        public bool $practiceMode = false
    ) {
    }
}