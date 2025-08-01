<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a quiz is completed.
 */
final class QuizCompleted extends AbstractDomainEvent
{
    public function __construct(
        int $quizResultId,
        private readonly int $categoryId,
        private readonly float $score,
        private readonly int $correctAnswers,
        private readonly int $totalQuestions
    ) {
        parent::__construct($quizResultId);
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getCorrectAnswers(): int
    {
        return $this->correctAnswers;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'category_id' => $this->categoryId,
            'score' => $this->score,
            'correct_answers' => $this->correctAnswers,
            'total_questions' => $this->totalQuestions,
        ]);
    }
}