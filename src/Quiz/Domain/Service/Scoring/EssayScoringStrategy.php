<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Scoring strategy for essay questions.
 * Requires manual grading, returns zero points by default.
 */
final class EssayScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        
        // Essays require manual grading
        // Return zero points until manually graded
        return Score::zero($maxPoints)
            ->withMetadata([
                'requires_manual_grading' => true,
                'user_essay' => $userAnswers[0] ?? '',
                'word_count' => str_word_count($userAnswers[0] ?? ''),
                'grading_status' => 'pending',
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getType()->isEssay();
    }
}