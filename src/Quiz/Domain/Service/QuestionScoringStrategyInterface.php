<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Interface for question scoring strategies.
 * Implements Strategy pattern for different question type scoring.
 */
interface QuestionScoringStrategyInterface
{
    /**
     * Calculate score for a question based on user answers.
     */
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score;

    /**
     * Check if this strategy supports the given question type.
     */
    public function supports(EnhancedQuestion $question): bool;
}