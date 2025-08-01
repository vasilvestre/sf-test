<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service\Scoring;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyInterface;
use App\Quiz\Domain\ValueObject\Score;

/**
 * Scoring strategy for multiple choice questions.
 * Supports partial credit based on correct answers selected.
 */
final class MultipleChoiceScoringStrategy implements QuestionScoringStrategyInterface
{
    public function calculateScore(EnhancedQuestion $question, array $userAnswers): Score
    {
        $maxPoints = $question->getScoringWeight();
        $correctAnswers = $question->getCorrectAnswers();
        $totalCorrect = count($correctAnswers);
        
        if ($totalCorrect === 0) {
            return Score::zero($maxPoints);
        }

        $correctSelected = 0;
        $incorrectSelected = 0;

        foreach ($userAnswers as $answerId) {
            $isCorrect = false;
            foreach ($correctAnswers as $correctAnswer) {
                if ((string)$correctAnswer->getId() === (string)$answerId) {
                    $isCorrect = true;
                    break;
                }
            }

            if ($isCorrect) {
                $correctSelected++;
            } else {
                $incorrectSelected++;
            }
        }

        // Calculate score with penalty for incorrect selections
        $correctPercentage = $totalCorrect > 0 ? $correctSelected / $totalCorrect : 0;
        $incorrectPenalty = $incorrectSelected * 0.1; // 10% penalty per incorrect selection
        
        $finalPercentage = max(0, $correctPercentage - $incorrectPenalty);
        $points = $finalPercentage * $maxPoints;

        return Score::create($points, $maxPoints)
            ->addToBreakdown('correct_answers', $correctSelected, $totalCorrect)
            ->addToBreakdown('incorrect_penalty', $incorrectSelected * $maxPoints * 0.1, $maxPoints)
            ->withMetadata([
                'correct_selected' => $correctSelected,
                'incorrect_selected' => $incorrectSelected,
                'total_correct' => $totalCorrect,
                'penalty_applied' => $incorrectPenalty > 0,
            ]);
    }

    public function supports(EnhancedQuestion $question): bool
    {
        return $question->getType()->isMultipleChoice();
    }
}