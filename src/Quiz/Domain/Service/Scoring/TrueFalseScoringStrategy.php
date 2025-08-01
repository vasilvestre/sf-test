<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Scoring strategy for true/false questions.
 * Simple binary scoring.
 */
final class TrueFalseScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        
        if (empty($userAnswers)) {
            return Score::zero($maxPoints);
        }

        $userAnswerId = $userAnswers[0] ?? null;
        if ($userAnswerId === null) {
            return Score::zero($maxPoints);
        }

        $correctAnswers = $question->getCorrectAnswers();
        if (empty($correctAnswers)) {
            return Score::zero($maxPoints);
        }

        $correctAnswer = $correctAnswers[0];
        $isCorrect = (string)$correctAnswer->getId() === (string)$userAnswerId;

        if ($isCorrect) {
            return Score::perfect($maxPoints)
                ->withMetadata([
                    'selected_answer' => $userAnswerId,
                    'correct_answer' => $correctAnswer->getId()->toString(),
                    'is_correct' => true,
                ]);
        }

        return Score::zero($maxPoints)
            ->withMetadata([
                'selected_answer' => $userAnswerId,
                'correct_answer' => $correctAnswer->getId()->toString(),
                'is_correct' => false,
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getType()->isTrueFalse();
    }
}