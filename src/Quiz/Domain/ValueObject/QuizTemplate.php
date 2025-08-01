<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing different quiz templates and modes.
 * Defines how quizzes behave and are presented to users.
 */
final class QuizTemplate extends AbstractValueObject
{
    public const PRACTICE_MODE = 'practice';
    public const EXAM_MODE = 'exam';
    public const CHALLENGE_MODE = 'challenge';
    public const REVIEW_MODE = 'review';
    public const CUSTOM_MODE = 'custom';

    private const VALID_TEMPLATES = [
        self::PRACTICE_MODE,
        self::EXAM_MODE,
        self::CHALLENGE_MODE,
        self::REVIEW_MODE,
        self::CUSTOM_MODE,
    ];

    public function __construct(
        private readonly string $mode,
        private readonly array $configuration = []
    ) {
        if (!in_array($mode, self::VALID_TEMPLATES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid quiz template "%s". Valid templates are: %s', $mode, implode(', ', self::VALID_TEMPLATES))
            );
        }
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function toString(): string
    {
        return $this->mode;
    }

    public function isPracticeMode(): bool
    {
        return $this->mode === self::PRACTICE_MODE;
    }

    public function isExamMode(): bool
    {
        return $this->mode === self::EXAM_MODE;
    }

    public function isChallengeMode(): bool
    {
        return $this->mode === self::CHALLENGE_MODE;
    }

    public function isReviewMode(): bool
    {
        return $this->mode === self::REVIEW_MODE;
    }

    public function isCustomMode(): bool
    {
        return $this->mode === self::CUSTOM_MODE;
    }

    public function allowsUnlimitedAttempts(): bool
    {
        return match ($this->mode) {
            self::PRACTICE_MODE => true,
            self::REVIEW_MODE => true,
            self::EXAM_MODE => false,
            self::CHALLENGE_MODE => false,
            self::CUSTOM_MODE => $this->getConfigValue('unlimited_attempts', false),
            default => false,
        };
    }

    public function showsImmediateFeedback(): bool
    {
        return match ($this->mode) {
            self::PRACTICE_MODE => true,
            self::REVIEW_MODE => true,
            self::EXAM_MODE => false,
            self::CHALLENGE_MODE => $this->getConfigValue('immediate_feedback', false),
            self::CUSTOM_MODE => $this->getConfigValue('immediate_feedback', true),
            default => true,
        };
    }

    public function allowsQuestionNavigation(): bool
    {
        return match ($this->mode) {
            self::PRACTICE_MODE => true,
            self::REVIEW_MODE => true,
            self::EXAM_MODE => $this->getConfigValue('allow_navigation', false),
            self::CHALLENGE_MODE => false,
            self::CUSTOM_MODE => $this->getConfigValue('allow_navigation', true),
            default => true,
        };
    }

    public function isTimeLimited(): bool
    {
        return match ($this->mode) {
            self::PRACTICE_MODE => false,
            self::REVIEW_MODE => false,
            self::EXAM_MODE => true,
            self::CHALLENGE_MODE => true,
            self::CUSTOM_MODE => $this->getConfigValue('time_limited', false),
            default => false,
        };
    }

    public function requiresCompletionInOrder(): bool
    {
        return match ($this->mode) {
            self::PRACTICE_MODE => false,
            self::REVIEW_MODE => false,
            self::EXAM_MODE => $this->getConfigValue('sequential_mode', true),
            self::CHALLENGE_MODE => true,
            self::CUSTOM_MODE => $this->getConfigValue('sequential_mode', false),
            default => false,
        };
    }

    public function getMaxAttempts(): ?int
    {
        if ($this->allowsUnlimitedAttempts()) {
            return null;
        }

        return match ($this->mode) {
            self::EXAM_MODE => $this->getConfigValue('max_attempts', 1),
            self::CHALLENGE_MODE => $this->getConfigValue('max_attempts', 3),
            self::CUSTOM_MODE => $this->getConfigValue('max_attempts', 1),
            default => 1,
        };
    }

    public function withConfiguration(array $configuration): self
    {
        return new self($this->mode, array_merge($this->configuration, $configuration));
    }

    // Factory methods
    public static function practiceMode(array $config = []): self
    {
        return new self(self::PRACTICE_MODE, $config);
    }

    public static function examMode(array $config = []): self
    {
        $defaultConfig = [
            'time_limited' => true,
            'sequential_mode' => true,
            'max_attempts' => 1,
            'immediate_feedback' => false,
            'allow_navigation' => false,
        ];
        return new self(self::EXAM_MODE, array_merge($defaultConfig, $config));
    }

    public static function challengeMode(array $config = []): self
    {
        $defaultConfig = [
            'time_limited' => true,
            'sequential_mode' => true,
            'max_attempts' => 3,
            'difficulty_progression' => true,
            'immediate_feedback' => false,
        ];
        return new self(self::CHALLENGE_MODE, array_merge($defaultConfig, $config));
    }

    public static function reviewMode(array $config = []): self
    {
        $defaultConfig = [
            'show_correct_answers' => true,
            'show_explanations' => true,
            'unlimited_attempts' => true,
        ];
        return new self(self::REVIEW_MODE, array_merge($defaultConfig, $config));
    }

    public static function customMode(array $config): self
    {
        return new self(self::CUSTOM_MODE, $config);
    }
}