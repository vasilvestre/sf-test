<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service;

use App\Quiz\Domain\ValueObject\QuestionType;
use App\Quiz\Domain\Service\Scoring\MultipleChoiceScoringStrategy;
use App\Quiz\Domain\Service\Scoring\SingleChoiceScoringStrategy;
use App\Quiz\Domain\Service\Scoring\TrueFalseScoringStrategy;
use App\Quiz\Domain\Service\Scoring\CodeCompletionScoringStrategy;
use App\Quiz\Domain\Service\Scoring\EssayScoringStrategy;
use App\Quiz\Domain\Service\Scoring\DefaultScoringStrategy;

/**
 * Factory for creating question scoring strategies.
 * Provides appropriate scoring strategy for each question type.
 */
final class QuestionScoringStrategyFactory
{
    private static array $strategies = [];

    public static function create(QuestionType $questionType): QuestionScoringStrategyInterface
    {
        if (empty(self::$strategies)) {
            self::initializeStrategies();
        }

        $typeValue = $questionType->getValue();

        if (!isset(self::$strategies[$typeValue])) {
            return new DefaultScoringStrategy();
        }

        return self::$strategies[$typeValue];
    }

    private static function initializeStrategies(): void
    {
        self::$strategies = [
            QuestionType::MULTIPLE_CHOICE => new MultipleChoiceScoringStrategy(),
            QuestionType::SINGLE_CHOICE => new SingleChoiceScoringStrategy(),
            QuestionType::TRUE_FALSE => new TrueFalseScoringStrategy(),
            QuestionType::CODE_COMPLETION => new CodeCompletionScoringStrategy(),
            QuestionType::ESSAY => new EssayScoringStrategy(),
            QuestionType::DRAG_AND_DROP => new MultipleChoiceScoringStrategy(), // Similar to multiple choice
            QuestionType::FILL_IN_THE_BLANK => new MultipleChoiceScoringStrategy(), // Similar to multiple choice
            QuestionType::MATCHING => new MultipleChoiceScoringStrategy(), // Similar to multiple choice
        ];
    }

    public static function registerStrategy(string $questionType, QuestionScoringStrategyInterface $strategy): void
    {
        self::$strategies[$questionType] = $strategy;
    }
}