<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Scoring strategy for code completion questions.
 * Requires manual grading, returns zero points by default.
 */
final class CodeCompletionScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        
        // Code completion requires manual grading
        // Return zero points until manually graded
        return Score::zero($maxPoints)
            ->withMetadata([
                'requires_manual_grading' => true,
                'user_code' => $userAnswers[0] ?? '',
                'grading_status' => 'pending',
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getType()->isCodeCompletion();
    }
}