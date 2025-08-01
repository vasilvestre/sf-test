<?php

declare(strict_types=1);

namespace App\Quiz\Application\Query;

use App\Shared\Application\Query\QueryInterface;

/**
 * Query to get detailed quiz session information including progress and analytics.
 * Provides comprehensive session data for display and analysis.
 */
final class GetQuizSessionDetailsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $userId,
        public readonly bool $includeQuestions = true,
        public readonly bool $includeAnswers = true,
        public readonly bool $includeProgress = true,
        public readonly bool $includeAnalytics = false,
        public readonly bool $includeAdaptiveData = false,
        public readonly bool $validateOwnership = true
    ) {
    }
}