<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\DifficultyLevel;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\Text;

/**
 * Question aggregate root representing a quiz question.
 */
final class Question extends AggregateRoot
{
    private ?Id $id = null;
    private Text $text;
    private ?Category $category = null;
    private DifficultyLevel $difficultyLevel;
    private \DateTimeImmutable $createdAt;

    /** @var Answer[] */
    private array $answers = [];

    public function __construct(Text $text, DifficultyLevel $difficultyLevel = null)
    {
        $this->text = $text;
        $this->difficultyLevel = $difficultyLevel ?? DifficultyLevel::medium();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Question ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getText(): Text
    {
        return $this->text;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getDifficultyLevel(): DifficultyLevel
    {
        return $this->difficultyLevel;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function assignToCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function updateText(Text $text): void
    {
        $this->text = $text;
    }

    public function updateDifficultyLevel(DifficultyLevel $difficultyLevel): void
    {
        $this->difficultyLevel = $difficultyLevel;
    }

    public function addAnswer(Answer $answer): void
    {
        if (!in_array($answer, $this->answers, true)) {
            $this->answers[] = $answer;
            $answer->assignToQuestion($this);
        }
    }

    public function removeAnswer(Answer $answer): void
    {
        $key = array_search($answer, $this->answers, true);
        if ($key !== false) {
            unset($this->answers[$key]);
            $this->answers = array_values($this->answers);
        }
    }

    /**
     * @return Answer[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @return Answer[]
     */
    public function getCorrectAnswers(): array
    {
        return array_filter($this->answers, fn(Answer $answer) => $answer->isCorrect());
    }

    /**
     * @return Answer[]
     */
    public function getIncorrectAnswers(): array
    {
        return array_filter($this->answers, fn(Answer $answer) => !$answer->isCorrect());
    }

    public function hasCorrectAnswer(): bool
    {
        return count($this->getCorrectAnswers()) > 0;
    }

    public function getAnswerCount(): int
    {
        return count($this->answers);
    }

    public function isValidQuestion(): bool
    {
        return $this->getAnswerCount() >= 2 && $this->hasCorrectAnswer();
    }
}