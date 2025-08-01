<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;

/**
 * Domain event fired when a question is answered incorrectly.
 */
final class QuestionAnsweredIncorrectly extends AbstractDomainEvent
{
    public function __construct(
        int $questionId,
        private readonly int $categoryId,
        private readonly array $selectedAnswers,
        private readonly array $correctAnswers
    ) {
        parent::__construct($questionId);
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getSelectedAnswers(): array
    {
        return $this->selectedAnswers;
    }

    public function getCorrectAnswers(): array
    {
        return $this->correctAnswers;
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'category_id' => $this->categoryId,
            'selected_answers' => $this->selectedAnswers,
            'correct_answers' => $this->correctAnswers,
        ]);
    }
}