<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Content;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuestionType;
use App\Quiz\Domain\ValueObject\Score;
use App\Quiz\Domain\ValueObject\Tag;
use App\Quiz\Domain\Event\QuestionCreated;
use App\Quiz\Domain\Event\QuestionContentUpdated;
use App\Quiz\Domain\Event\QuestionDifficultyChanged;
use App\Quiz\Domain\Event\AnswerAddedToQuestion;
use App\Quiz\Domain\Event\AnswerRemovedFromQuestion;
use App\Quiz\Domain\Service\QuestionScoringStrategyFactory;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * Enhanced Question aggregate root supporting multiple question types and rich content.
 * Represents a quiz question with advanced features for modern e-learning.
 */
final class EnhancedQuestion extends AggregateRoot
{
    private ?Id $id = null;
    private Content $content;
    private QuestionType $type;
    private EnhancedDifficultyLevel $difficultyLevel;
    private float $scoringWeight;
    private ?Content $explanation = null;
    private ?Content $hint = null;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Tag[] */
    private array $tags = [];

    /** @var EnhancedAnswer[] */
    private array $answers = [];

    /** @var array */
    private array $metadata = [];

    public function __construct(
        Content $content,
        QuestionType $type,
        EnhancedDifficultyLevel $difficultyLevel = null,
        float $scoringWeight = 1.0
    ) {
        $this->content = $content;
        $this->type = $type;
        $this->difficultyLevel = $difficultyLevel ?? EnhancedDifficultyLevel::medium();
        $this->scoringWeight = $this->validateScoringWeight($scoringWeight);
        $this->createdAt = new \DateTimeImmutable();

        // Record domain event
        $this->recordEvent(new QuestionCreated($this));
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

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getType(): QuestionType
    {
        return $this->type;
    }

    public function getDifficultyLevel(): EnhancedDifficultyLevel
    {
        return $this->difficultyLevel;
    }

    public function getScoringWeight(): float
    {
        return $this->scoringWeight;
    }

    public function getExplanation(): ?Content
    {
        return $this->explanation;
    }

    public function getHint(): ?Content
    {
        return $this->hint;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return EnhancedAnswer[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // Content management
    public function updateContent(Content $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
        $this->recordEvent(new QuestionContentUpdated($this));
    }

    public function setExplanation(Content $explanation): void
    {
        $this->explanation = $explanation;
        $this->markAsUpdated();
    }

    public function setHint(Content $hint): void
    {
        $this->hint = $hint;
        $this->markAsUpdated();
    }

    public function removeExplanation(): void
    {
        $this->explanation = null;
        $this->markAsUpdated();
    }

    public function removeHint(): void
    {
        $this->hint = null;
        $this->markAsUpdated();
    }

    // Difficulty and scoring
    public function updateDifficultyLevel(EnhancedDifficultyLevel $difficultyLevel): void
    {
        $oldLevel = $this->difficultyLevel;
        $this->difficultyLevel = $difficultyLevel;
        $this->markAsUpdated();
        $this->recordEvent(new QuestionDifficultyChanged($this, $oldLevel, $difficultyLevel));
    }

    public function updateScoringWeight(float $weight): void
    {
        $this->scoringWeight = $this->validateScoringWeight($weight);
        $this->markAsUpdated();
    }

    // Tag management
    public function addTag(Tag $tag): void
    {
        foreach ($this->tags as $existingTag) {
            if ($existingTag->equals($tag)) {
                return; // Tag already exists
            }
        }

        $this->tags[] = $tag;
        $this->markAsUpdated();
    }

    public function removeTag(Tag $tag): void
    {
        foreach ($this->tags as $index => $existingTag) {
            if ($existingTag->equals($tag)) {
                unset($this->tags[$index]);
                $this->tags = array_values($this->tags);
                $this->markAsUpdated();
                return;
            }
        }
    }

    public function hasTag(Tag $tag): bool
    {
        foreach ($this->tags as $existingTag) {
            if ($existingTag->equals($tag)) {
                return true;
            }
        }
        return false;
    }

    public function getTagsByCategory(string $category): array
    {
        return array_filter($this->tags, fn(Tag $tag) => $tag->isInCategory($category));
    }

    // Answer management
    public function addAnswer(EnhancedAnswer $answer): void
    {
        if (!$this->canAddAnswer()) {
            throw new \DomainException('Cannot add more answers to this question type');
        }

        if ($this->hasAnswer($answer)) {
            return; // Answer already exists
        }

        $this->answers[] = $answer;
        $answer->assignToQuestion($this);
        $this->markAsUpdated();
        $this->recordEvent(new AnswerAddedToQuestion($this, $answer));
    }

    public function removeAnswer(EnhancedAnswer $answer): void
    {
        foreach ($this->answers as $index => $existingAnswer) {
            if ($existingAnswer->equals($answer)) {
                unset($this->answers[$index]);
                $this->answers = array_values($this->answers);
                $this->markAsUpdated();
                $this->recordEvent(new AnswerRemovedFromQuestion($this, $answer));
                return;
            }
        }
    }

    public function hasAnswer(EnhancedAnswer $answer): bool
    {
        foreach ($this->answers as $existingAnswer) {
            if ($existingAnswer->equals($answer)) {
                return true;
            }
        }
        return false;
    }

    public function getCorrectAnswers(): array
    {
        return array_filter($this->answers, fn(EnhancedAnswer $answer) => $answer->isCorrect());
    }

    public function getIncorrectAnswers(): array
    {
        return array_filter($this->answers, fn(EnhancedAnswer $answer) => !$answer->isCorrect());
    }

    // Validation and business rules
    public function isValid(): bool
    {
        if (empty($this->answers)) {
            return false;
        }

        // Type-specific validation
        switch (true) {
            case $this->type->isTrueFalse():
                return count($this->answers) === 2 && count($this->getCorrectAnswers()) === 1;

            case $this->type->isSingleChoice():
                return count($this->answers) >= 2 && count($this->getCorrectAnswers()) === 1;

            case $this->type->isMultipleChoice():
                return count($this->answers) >= 2 && count($this->getCorrectAnswers()) >= 1;

            case $this->type->isEssay():
                return true; // Essays don't require predefined answers

            case $this->type->isCodeCompletion():
                return count($this->getCorrectAnswers()) >= 1;

            case $this->type->isDragAndDrop():
            case $this->type->isMatching():
            case $this->type->isFillInTheBlank():
                return count($this->answers) >= 2 && count($this->getCorrectAnswers()) >= 1;

            default:
                return count($this->answers) >= 1 && count($this->getCorrectAnswers()) >= 1;
        }
    }

    public function canAddAnswer(): bool
    {
        // True/False questions can only have 2 answers
        if ($this->type->isTrueFalse()) {
            return count($this->answers) < 2;
        }

        // Essay questions don't need predefined answers
        if ($this->type->isEssay()) {
            return false;
        }

        // Most other types can have multiple answers
        return count($this->answers) < 10; // Reasonable limit
    }

    public function requiresManualGrading(): bool
    {
        return $this->type->requiresManualGrading();
    }

    public function supportsPartialCredit(): bool
    {
        return $this->type->supportsPartialCredit();
    }

    // Scoring
    public function calculateScore(array $userAnswers): Score
    {
        $strategy = QuestionScoringStrategyFactory::create($this->type);
        return $strategy->calculateScore($this, $userAnswers);
    }

    public function getMaximumScore(): Score
    {
        return Score::create($this->scoringWeight, $this->scoringWeight);
    }

    // Metadata management
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->markAsUpdated();
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->markAsUpdated();
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    // Helper methods
    private function validateScoringWeight(float $weight): float
    {
        if ($weight <= 0) {
            throw new \InvalidArgumentException('Scoring weight must be positive');
        }

        if ($weight > 100) {
            throw new \InvalidArgumentException('Scoring weight cannot exceed 100');
        }

        return $weight;
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Factory methods
    public static function multipleChoice(Content $content, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($content, QuestionType::multipleChoice(), $difficulty);
    }

    public static function singleChoice(Content $content, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($content, QuestionType::singleChoice(), $difficulty);
    }

    public static function trueFalse(Content $content, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($content, QuestionType::trueFalse(), $difficulty);
    }

    public static function codeCompletion(Content $content, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($content, QuestionType::codeCompletion(), $difficulty);
    }

    public static function essay(Content $content, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($content, QuestionType::essay(), $difficulty);
    }
}