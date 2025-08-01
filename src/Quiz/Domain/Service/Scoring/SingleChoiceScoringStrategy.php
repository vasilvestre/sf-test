<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Scoring strategy for single choice questions.
 * Simple correct/incorrect scoring.
 */
final class SingleChoiceScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        
        if (empty($userAnswers)) {
            return Score::zero($maxPoints);
        }

        // Single choice should have exactly one answer
        $userAnswerId = $userAnswers[0] ?? null;
        if ($userAnswerId === null) {
            return Score::zero($maxPoints);
        }

        $correctAnswers = $question->getCorrectAnswers();
        if (empty($correctAnswers)) {
            return Score::zero($maxPoints);
        }

        // Check if the selected answer is correct
        foreach ($correctAnswers as $correctAnswer) {
            if ((string)$correctAnswer->getId() === (string)$userAnswerId) {
                return Score::perfect($maxPoints)
                    ->withMetadata([
                        'selected_answer' => $userAnswerId,
                        'is_correct' => true,
                    ]);
            }
        }

        return Score::zero($maxPoints)
            ->withMetadata([
                'selected_answer' => $userAnswerId,
                'is_correct' => false,
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getType()->isSingleChoice();
    }
}