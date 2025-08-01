<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Score;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * UserAnswer entity representing a user's answer to a specific question.
 * Tracks the answer content, timing, and scoring information.
 */
final class UserAnswer extends AggregateRoot
{
    private ?Id $id = null;
    private Id $questionId;
    private array $answerIds = [];
    private ?string $textAnswer = null;
    private \DateTimeImmutable $answeredAt;
    private ?int $timeSpentSeconds = null;
    private ?Score $score = null;
    private bool $isCorrect = false;
    private array $metadata = [];

    public function __construct(
        Id $questionId,
        array $answerIds = [],
        ?string $textAnswer = null,
        ?int $timeSpentSeconds = null
    ) {
        $this->questionId = $questionId;
        $this->answerIds = $answerIds;
        $this->textAnswer = $textAnswer;
        $this->timeSpentSeconds = $timeSpentSeconds;
        $this->answeredAt = new \DateTimeImmutable();

        if (empty($answerIds) && empty($textAnswer)) {
            throw new \InvalidArgumentException('User answer must have either answer IDs or text answer');
        }
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('UserAnswer ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getQuestionId(): Id
    {
        return $this->questionId;
    }

    public function getAnswerIds(): array
    {
        return $this->answerIds;
    }

    public function getTextAnswer(): ?string
    {
        return $this->textAnswer;
    }

    public function getAnsweredAt(): \DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function getTimeSpentSeconds(): ?int
    {
        return $this->timeSpentSeconds;
    }

    public function getScore(): ?Score
    {
        return $this->score;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // Answer management
    public function updateAnswerIds(array $answerIds): void
    {
        $this->answerIds = $answerIds;
        $this->updateAnsweredAt();
    }

    public function updateTextAnswer(string $textAnswer): void
    {
        $this->textAnswer = $textAnswer;
        $this->updateAnsweredAt();
    }

    public function setTimeSpent(int $seconds): void
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Time spent cannot be negative');
        }
        $this->timeSpentSeconds = $seconds;
    }

    // Scoring
    public function setScore(Score $score, bool $isCorrect): void
    {
        $this->score = $score;
        $this->isCorrect = $isCorrect;
    }

    public function markAsCorrect(Score $score): void
    {
        $this->score = $score;
        $this->isCorrect = true;
    }

    public function markAsIncorrect(Score $score): void
    {
        $this->score = $score;
        $this->isCorrect = false;
    }

    // Type checking
    public function hasMultipleChoiceAnswers(): bool
    {
        return !empty($this->answerIds);
    }

    public function hasTextAnswer(): bool
    {
        return !empty($this->textAnswer);
    }

    public function isAnswered(): bool
    {
        return $this->hasMultipleChoiceAnswers() || $this->hasTextAnswer();
    }

    public function isScored(): bool
    {
        return $this->score !== null;
    }

    // Metadata management
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    // Helper methods
    private function updateAnsweredAt(): void
    {
        $this->answeredAt = new \DateTimeImmutable();
    }

    // Factory methods
    public static function multipleChoice(Id $questionId, array $answerIds, ?int $timeSpent = null): self
    {
        return new self($questionId, $answerIds, null, $timeSpent);
    }

    public static function textBased(Id $questionId, string $textAnswer, ?int $timeSpent = null): self
    {
        return new self($questionId, [], $textAnswer, $timeSpent);
    }

    public static function singleChoice(Id $questionId, Id $answerId, ?int $timeSpent = null): self
    {
        return new self($questionId, [$answerId], null, $timeSpent);
    }
}