<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get adaptive learning data and personalized recommendations.
 * Provides ML-driven insights and next best actions for the learner.
 */
final class GetAdaptiveLearningDataQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $sessionId = null,
        public readonly ?int $categoryId = null,
        public readonly int $recommendationCount = 10,
        public readonly string $recommendationStrategy = 'adaptive', // adaptive, knowledge_gap, performance_based, mixed
        public readonly bool $includeExplanations = true,
        public readonly bool $includeAlternatives = true,
        public readonly bool $includePredictions = true,
        public readonly bool $includePersonalizedInsights = true,
        public readonly array $excludeQuestionIds = [],
        public readonly ?string $targetDifficulty = null
    ) {
    }
}