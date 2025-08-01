<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\Event\QuizCompleted;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\Score;

/**
 * QuizResult aggregate root representing the result of a completed quiz.
 */
final class QuizResult extends AggregateRoot
{
    private ?Id $id = null;
    private ?Category $category = null;
    private Score $score;
    private int $correctAnswers;
    private int $totalQuestions;
    private \DateTimeImmutable $createdAt;
    private array $questionsData = [];

    public function __construct(
        Score $score,
        int $correctAnswers,
        int $totalQuestions,
        ?Category $category = null,
        array $questionsData = []
    ) {
        if ($correctAnswers < 0 || $correctAnswers > $totalQuestions) {
            throw new \InvalidArgumentException('Correct answers must be between 0 and total questions');
        }

        if ($totalQuestions <= 0) {
            throw new \InvalidArgumentException('Total questions must be a positive integer');
        }

        $this->score = $score;
        $this->correctAnswers = $correctAnswers;
        $this->totalQuestions = $totalQuestions;
        $this->category = $category;
        $this->questionsData = $questionsData;
        $this->createdAt = new \DateTimeImmutable();

        // Record domain event
        $this->recordEvent(new QuizCompleted(
            $this->id?->getValue() ?? 0, // Will be set after persistence
            $this->category?->getId()?->getValue() ?? 0,
            $this->score->getValue(),
            $this->correctAnswers,
            $this->totalQuestions
        ));
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('QuizResult ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getScore(): Score
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getQuestionsData(): array
    {
        return $this->questionsData;
    }

    public function isPassingScore(float $threshold = 60.0): bool
    {
        return $this->score->isPassingScore($threshold);
    }

    public function getSuccessRate(): float
    {
        return $this->score->getValue();
    }
}