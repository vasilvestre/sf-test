<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service;

use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuizTemplate;
use App\Quiz\Domain\ValueObject\Tag;
use App\Quiz\Domain\ValueObject\TimeLimit;

/**
 * Value object representing criteria for quiz generation.
 * Encapsulates all parameters needed to generate a customized quiz.
 */
final class QuizGenerationCriteria
{
    public function __construct(
        private readonly string $title,
        private readonly int $questionCount,
        private readonly QuizTemplate $template,
        private readonly EnhancedDifficultyLevel $targetDifficulty,
        private readonly array $categoryIds = [],
        private readonly array $tags = [],
        private readonly array $questionTypes = [],
        private readonly array $questionTypeDistribution = [],
        private readonly ?TimeLimit $timeLimit = null,
        private readonly bool $balanceDifficulty = true,
        private readonly bool $allowRepeatQuestions = false,
        private readonly array $excludeQuestionIds = [],
        private readonly array $scoringRules = [],
        private readonly array $metadata = []
    ) {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        if ($questionCount <= 0) {
            throw new \InvalidArgumentException('Question count must be positive');
        }

        if ($questionCount > 1000) {
            throw new \InvalidArgumentException('Question count cannot exceed 1000');
        }

        $this->validateQuestionTypeDistribution($questionTypeDistribution);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getQuestionCount(): int
    {
        return $this->questionCount;
    }

    public function getTemplate(): QuizTemplate
    {
        return $this->template;
    }

    public function getTargetDifficulty(): EnhancedDifficultyLevel
    {
        return $this->targetDifficulty;
    }

    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getQuestionTypes(): array
    {
        return $this->questionTypes;
    }

    public function getQuestionTypeDistribution(): array
    {
        return $this->questionTypeDistribution;
    }

    public function getTimeLimit(): ?TimeLimit
    {
        return $this->timeLimit;
    }

    public function shouldBalanceDifficulty(): bool
    {
        return $this->balanceDifficulty;
    }

    public function allowsRepeatQuestions(): bool
    {
        return $this->allowRepeatQuestions;
    }

    public function getExcludeQuestionIds(): array
    {
        return $this->excludeQuestionIds;
    }

    public function getScoringRules(): array
    {
        return $this->scoringRules;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function hasCategories(): bool
    {
        return !empty($this->categoryIds);
    }

    public function hasTags(): bool
    {
        return !empty($this->tags);
    }

    public function hasQuestionTypes(): bool
    {
        return !empty($this->questionTypes);
    }

    public function hasTypeDistribution(): bool
    {
        return !empty($this->questionTypeDistribution);
    }

    public function hasTimeLimit(): bool
    {
        return $this->timeLimit !== null;
    }

    public function hasExclusions(): bool
    {
        return !empty($this->excludeQuestionIds);
    }

    private function validateQuestionTypeDistribution(array $distribution): void
    {
        if (empty($distribution)) {
            return;
        }

        $totalPercentage = array_sum($distribution);
        if ($totalPercentage > 100) {
            throw new \InvalidArgumentException('Question type distribution cannot exceed 100%');
        }

        foreach ($distribution as $type => $percentage) {
            if (!is_string($type)) {
                throw new \InvalidArgumentException('Question type must be a string');
            }

            if (!is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
                throw new \InvalidArgumentException('Distribution percentage must be between 0 and 100');
            }
        }
    }

    // Builder pattern methods for easier construction
    public function withCategories(array $categoryIds): self
    {
        return new self(
            $this->title,
            $this->questionCount,
            $this->template,
            $this->targetDifficulty,
            $categoryIds,
            $this->tags,
            $this->questionTypes,
            $this->questionTypeDistribution,
            $this->timeLimit,
            $this->balanceDifficulty,
            $this->allowRepeatQuestions,
            $this->excludeQuestionIds,
            $this->scoringRules,
            $this->metadata
        );
    }

    public function withTags(array $tags): self
    {
        return new self(
            $this->title,
            $this->questionCount,
            $this->template,
            $this->targetDifficulty,
            $this->categoryIds,
            $tags,
            $this->questionTypes,
            $this->questionTypeDistribution,
            $this->timeLimit,
            $this->balanceDifficulty,
            $this->allowRepeatQuestions,
            $this->excludeQuestionIds,
            $this->scoringRules,
            $this->metadata
        );
    }

    public function withQuestionTypes(array $questionTypes): self
    {
        return new self(
            $this->title,
            $this->questionCount,
            $this->template,
            $this->targetDifficulty,
            $this->categoryIds,
            $this->tags,
            $questionTypes,
            $this->questionTypeDistribution,
            $this->timeLimit,
            $this->balanceDifficulty,
            $this->allowRepeatQuestions,
            $this->excludeQuestionIds,
            $this->scoringRules,
            $this->metadata
        );
    }

    public function withTimeLimit(TimeLimit $timeLimit): self
    {
        return new self(
            $this->title,
            $this->questionCount,
            $this->template,
            $this->targetDifficulty,
            $this->categoryIds,
            $this->tags,
            $this->questionTypes,
            $this->questionTypeDistribution,
            $timeLimit,
            $this->balanceDifficulty,
            $this->allowRepeatQuestions,
            $this->excludeQuestionIds,
            $this->scoringRules,
            $this->metadata
        );
    }

    // Factory methods
    public static function practice(string $title, int $questionCount, EnhancedDifficultyLevel $difficulty): self
    {
        return new self($title, $questionCount, QuizTemplate::practiceMode(), $difficulty);
    }

    public static function exam(string $title, int $questionCount, EnhancedDifficultyLevel $difficulty, TimeLimit $timeLimit): self
    {
        return new self($title, $questionCount, QuizTemplate::examMode(), $difficulty, [], [], [], [], $timeLimit);
    }

    public static function challenge(string $title, int $questionCount): self
    {
        return new self($title, $questionCount, QuizTemplate::challengeMode(), EnhancedDifficultyLevel::medium());
    }
}