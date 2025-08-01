<?php

declare(strict_types=1);

namespace App\Quiz\Domain\ValueObject;

use App\Shared\Domain\ValueObject\AbstractValueObject;

/**
 * Value object representing different types of quiz questions.
 * Supports multiple question formats for rich learning experiences.
 */
final class QuestionType extends AbstractValueObject
{
    public const MULTIPLE_CHOICE = 'multiple_choice';
    public const SINGLE_CHOICE = 'single_choice';
    public const TRUE_FALSE = 'true_false';
    public const CODE_COMPLETION = 'code_completion';
    public const DRAG_AND_DROP = 'drag_and_drop';
    public const FILL_IN_THE_BLANK = 'fill_in_the_blank';
    public const ESSAY = 'essay';
    public const MATCHING = 'matching';

    private const VALID_TYPES = [
        self::MULTIPLE_CHOICE,
        self::SINGLE_CHOICE,
        self::TRUE_FALSE,
        self::CODE_COMPLETION,
        self::DRAG_AND_DROP,
        self::FILL_IN_THE_BLANK,
        self::ESSAY,
        self::MATCHING,
    ];

    public function __construct(
        private readonly string $value
    ) {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid question type "%s". Valid types are: %s', $value, implode(', ', self::VALID_TYPES))
            );
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isMultipleChoice(): bool
    {
        return $this->value === self::MULTIPLE_CHOICE;
    }

    public function isSingleChoice(): bool
    {
        return $this->value === self::SINGLE_CHOICE;
    }

    public function isTrueFalse(): bool
    {
        return $this->value === self::TRUE_FALSE;
    }

    public function isCodeCompletion(): bool
    {
        return $this->value === self::CODE_COMPLETION;
    }

    public function isDragAndDrop(): bool
    {
        return $this->value === self::DRAG_AND_DROP;
    }

    public function isFillInTheBlank(): bool
    {
        return $this->value === self::FILL_IN_THE_BLANK;
    }

    public function isEssay(): bool
    {
        return $this->value === self::ESSAY;
    }

    public function isMatching(): bool
    {
        return $this->value === self::MATCHING;
    }

    public function allowsMultipleCorrectAnswers(): bool
    {
        return in_array($this->value, [
            self::MULTIPLE_CHOICE,
            self::DRAG_AND_DROP,
            self::FILL_IN_THE_BLANK,
            self::MATCHING,
        ], true);
    }

    public function requiresManualGrading(): bool
    {
        return in_array($this->value, [
            self::ESSAY,
            self::CODE_COMPLETION,
        ], true);
    }

    public function supportsPartialCredit(): bool
    {
        return in_array($this->value, [
            self::MULTIPLE_CHOICE,
            self::DRAG_AND_DROP,
            self::FILL_IN_THE_BLANK,
            self::MATCHING,
            self::CODE_COMPLETION,
        ], true);
    }

    // Factory methods
    public static function multipleChoice(): self
    {
        return new self(self::MULTIPLE_CHOICE);
    }

    public static function singleChoice(): self
    {
        return new self(self::SINGLE_CHOICE);
    }

    public static function trueFalse(): self
    {
        return new self(self::TRUE_FALSE);
    }

    public static function codeCompletion(): self
    {
        return new self(self::CODE_COMPLETION);
    }

    public static function dragAndDrop(): self
    {
        return new self(self::DRAG_AND_DROP);
    }

    public static function fillInTheBlank(): self
    {
        return new self(self::FILL_IN_THE_BLANK);
    }

    public static function essay(): self
    {
        return new self(self::ESSAY);
    }

    public static function matching(): self
    {
        return new self(self::MATCHING);
    }
}