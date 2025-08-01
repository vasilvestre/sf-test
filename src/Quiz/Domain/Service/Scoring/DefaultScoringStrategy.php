<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Default scoring strategy for unsupported question types.
 * Provides basic scoring logic.
 */
final class DefaultScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        
        if (empty($userAnswers)) {
            return Score::zero($maxPoints);
        }

        // Basic scoring: check if any user answer matches any correct answer
        $correctAnswers = $question->getCorrectAnswers();
        if (empty($correctAnswers)) {
            return Score::zero($maxPoints);
        }

        foreach ($userAnswers as $userAnswer) {
            foreach ($correctAnswers as $correctAnswer) {
                if ((string)$correctAnswer->getId() === (string)$userAnswer) {
                    return Score::perfect($maxPoints)
                        ->withMetadata([
                            'scoring_strategy' => 'default',
                            'matched_answer' => $userAnswer,
                        ]);
                }
            }
        }

        return Score::zero($maxPoints)
            ->withMetadata([
                'scoring_strategy' => 'default',
                'no_match_found' => true,
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return true; // Default strategy supports all question types
    }
}