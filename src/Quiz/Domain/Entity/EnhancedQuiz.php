<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Entity;

use App\Quiz\Domain\ValueObject\Content;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuizTemplate;
use App\Quiz\Domain\ValueObject\Score;
use App\Quiz\Domain\ValueObject\Tag;
use App\Quiz\Domain\ValueObject\TimeLimit;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\Id;

/**
 * Enhanced Quiz aggregate root supporting advanced features.
 * Represents a complete quiz with questions, templates, and configuration.
 */
final class EnhancedQuiz extends AggregateRoot
{
    private ?Id $id = null;
    private string $title;
    private ?Content $description = null;
    private QuizTemplate $template;
    private ?TimeLimit $timeLimit = null;
    private EnhancedDifficultyLevel $targetDifficulty;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;
    private ?\DateTimeImmutable $publishedAt = null;
    private bool $isActive = true;

    /** @var EnhancedCategory[] */
    private array $categories = [];

    /** @var EnhancedQuestion[] */
    private array $questions = [];

    /** @var Tag[] */
    private array $tags = [];

    /** @var array */
    private array $metadata = [];

    /** @var array */
    private array $scoringRules = [];

    public function __construct(
        string $title,
        QuizTemplate $template,
        EnhancedDifficultyLevel $targetDifficulty = null
    ) {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Quiz title cannot be empty');
        }

        $this->title = $title;
        $this->template = $template;
        $this->targetDifficulty = $targetDifficulty ?? EnhancedDifficultyLevel::medium();
        $this->createdAt = new \DateTimeImmutable();

        $this->recordEvent(new QuizCreated($this));
    }

    public function getId(): ?Id
    {
        return $this->id;
    }

    public function setId(Id $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('Quiz ID cannot be changed once set');
        }
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?Content
    {
        return $this->description;
    }

    public function getTemplate(): QuizTemplate
    {
        return $this->template;
    }

    public function getTimeLimit(): ?TimeLimit
    {
        return $this->timeLimit;
    }

    public function getTargetDifficulty(): EnhancedDifficultyLevel
    {
        return $this->targetDifficulty;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null;
    }

    /**
     * @return EnhancedCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return EnhancedQuestion[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getScoringRules(): array
    {
        return $this->scoringRules;
    }

    // Basic information management
    public function updateTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Quiz title cannot be empty');
        }

        $this->title = $title;
        $this->markAsUpdated();
    }

    public function setDescription(Content $description): void
    {
        $this->description = $description;
        $this->markAsUpdated();
    }

    public function removeDescription(): void
    {
        $this->description = null;
        $this->markAsUpdated();
    }

    // Template and configuration
    public function updateTemplate(QuizTemplate $template): void
    {
        $this->template = $template;
        $this->markAsUpdated();
        $this->recordEvent(new QuizTemplateChanged($this, $template));
    }

    public function setTimeLimit(TimeLimit $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
        $this->markAsUpdated();
    }

    public function removeTimeLimit(): void
    {
        $this->timeLimit = null;
        $this->markAsUpdated();
    }

    public function updateTargetDifficulty(EnhancedDifficultyLevel $difficulty): void
    {
        $this->targetDifficulty = $difficulty;
        $this->markAsUpdated();
    }

    // Status management
    public function activate(): void
    {
        $this->isActive = true;
        $this->markAsUpdated();
        $this->recordEvent(new QuizActivated($this));
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->markAsUpdated();
        $this->recordEvent(new QuizDeactivated($this));
    }

    public function publish(): void
    {
        if (!$this->isValidForPublication()) {
            throw new \DomainException('Quiz is not valid for publication');
        }

        $this->publishedAt = new \DateTimeImmutable();
        $this->markAsUpdated();
        $this->recordEvent(new QuizPublished($this));
    }

    public function unpublish(): void
    {
        $this->publishedAt = null;
        $this->markAsUpdated();
        $this->recordEvent(new QuizUnpublished($this));
    }

    // Category management
    public function addCategory(EnhancedCategory $category): void
    {
        if ($this->hasCategory($category)) {
            return;
        }

        $this->categories[] = $category;
        $this->markAsUpdated();
        $this->recordEvent(new CategoryAddedToQuiz($this, $category));
    }

    public function removeCategory(EnhancedCategory $category): void
    {
        foreach ($this->categories as $index => $existingCategory) {
            if ($existingCategory->equals($category)) {
                unset($this->categories[$index]);
                $this->categories = array_values($this->categories);
                $this->markAsUpdated();
                $this->recordEvent(new CategoryRemovedFromQuiz($this, $category));
                return;
            }
        }
    }

    public function hasCategory(EnhancedCategory $category): bool
    {
        foreach ($this->categories as $existingCategory) {
            if ($existingCategory->equals($category)) {
                return true;
            }
        }
        return false;
    }

    // Question management
    public function addQuestion(EnhancedQuestion $question): void
    {
        if ($this->hasQuestion($question)) {
            return;
        }

        $this->questions[] = $question;
        $this->markAsUpdated();
        $this->recordEvent(new QuestionAddedToQuiz($this, $question));
    }

    public function removeQuestion(EnhancedQuestion $question): void
    {
        foreach ($this->questions as $index => $existingQuestion) {
            if ($existingQuestion->equals($question)) {
                unset($this->questions[$index]);
                $this->questions = array_values($this->questions);
                $this->markAsUpdated();
                $this->recordEvent(new QuestionRemovedFromQuiz($this, $question));
                return;
            }
        }
    }

    public function hasQuestion(EnhancedQuestion $question): bool
    {
        foreach ($this->questions as $existingQuestion) {
            if ($existingQuestion->equals($question)) {
                return true;
            }
        }
        return false;
    }

    public function getQuestionCount(): int
    {
        return count($this->questions);
    }

    public function getQuestionsInDifficultyRange(EnhancedDifficultyLevel $min, EnhancedDifficultyLevel $max): array
    {
        return array_filter($this->questions, function (EnhancedQuestion $question) use ($min, $max) {
            $difficulty = $question->getDifficultyLevel();
            return $difficulty->getLevel() >= $min->getLevel() && $difficulty->getLevel() <= $max->getLevel();
        });
    }

    public function getQuestionsByType(string $questionType): array
    {
        return array_filter($this->questions, function (EnhancedQuestion $question) use ($questionType) {
            return $question->getType()->getValue() === $questionType;
        });
    }

    // Tag management
    public function addTag(Tag $tag): void
    {
        if ($this->hasTag($tag)) {
            return;
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

    // Scoring and validation
    public function getMaximumScore(): Score
    {
        $totalWeight = array_reduce(
            $this->questions,
            fn(float $total, EnhancedQuestion $question) => $total + $question->getScoringWeight(),
            0.0
        );

        return Score::create($totalWeight, $totalWeight);
    }

    public function getAverageDifficulty(): EnhancedDifficultyLevel
    {
        if (empty($this->questions)) {
            return $this->targetDifficulty;
        }

        $totalDifficulty = array_reduce(
            $this->questions,
            fn(int $total, EnhancedQuestion $question) => $total + $question->getDifficultyLevel()->getLevel(),
            0
        );

        $averageLevel = intval(round($totalDifficulty / count($this->questions)));
        return EnhancedDifficultyLevel::fromLevel($averageLevel);
    }

    public function isValidForPublication(): bool
    {
        if (empty($this->questions)) {
            return false;
        }

        // All questions must be valid
        foreach ($this->questions as $question) {
            if (!$question->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function canBeAttempted(): bool
    {
        return $this->isActive && $this->isPublished() && $this->isValidForPublication();
    }

    // Metadata and scoring rules
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

    public function setScoringRules(array $rules): void
    {
        $this->scoringRules = $rules;
        $this->markAsUpdated();
    }

    public function addScoringRule(string $rule, mixed $value): void
    {
        $this->scoringRules[$rule] = $value;
        $this->markAsUpdated();
    }

    // Helper methods
    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Factory methods
    public static function practice(string $title, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($title, QuizTemplate::practiceMode(), $difficulty);
    }

    public static function exam(string $title, TimeLimit $timeLimit, EnhancedDifficultyLevel $difficulty = null): self
    {
        $quiz = new self($title, QuizTemplate::examMode(), $difficulty);
        $quiz->setTimeLimit($timeLimit);
        return $quiz;
    }

    public static function challenge(string $title, EnhancedDifficultyLevel $difficulty = null): self
    {
        return new self($title, QuizTemplate::challengeMode(), $difficulty);
    }

    public static function review(string $title): self
    {
        return new self($title, QuizTemplate::reviewMode());
    }
}