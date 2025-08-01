<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Service;

use App\Quiz\Domain\Entity\EnhancedCategory;
use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Entity\EnhancedQuiz;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuizTemplate;
use App\Quiz\Domain\ValueObject\Tag;

/**
 * Domain service for generating quizzes based on criteria.
 * Implements intelligent question selection and difficulty balancing.
 */
final class QuizGeneratorService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly DifficultyCalculatorService $difficultyCalculator
    ) {
    }

    /**
     * Generate a quiz based on specified criteria.
     */
    public function generateQuiz(QuizGenerationCriteria $criteria): EnhancedQuiz
    {
        $questions = $this->selectQuestions($criteria);
        
        if (empty($questions)) {
            throw new \DomainException('No questions available matching the criteria');
        }

        $quiz = new EnhancedQuiz(
            $criteria->getTitle(),
            $criteria->getTemplate(),
            $criteria->getTargetDifficulty()
        );

        // Add selected questions
        foreach ($questions as $question) {
            $quiz->addQuestion($question);
        }

        // Add categories from questions
        $categories = $this->extractCategoriesFromQuestions($questions);
        foreach ($categories as $category) {
            $quiz->addCategory($category);
        }

        // Add tags from criteria
        foreach ($criteria->getTags() as $tag) {
            $quiz->addTag($tag);
        }

        // Set time limit if specified
        if ($criteria->getTimeLimit() !== null) {
            $quiz->setTimeLimit($criteria->getTimeLimit());
        }

        // Apply scoring rules
        if (!empty($criteria->getScoringRules())) {
            $quiz->setScoringRules($criteria->getScoringRules());
        }

        return $quiz;
    }

    /**
     * Select questions based on criteria with intelligent balancing.
     */
    private function selectQuestions(QuizGenerationCriteria $criteria): array
    {
        $allQuestions = $this->questionRepository->findByCriteria($criteria);
        
        if (empty($allQuestions)) {
            return [];
        }

        $selectedQuestions = [];
        $targetCount = $criteria->getQuestionCount();
        $targetDifficulty = $criteria->getTargetDifficulty();

        // Group questions by difficulty level
        $questionsByDifficulty = $this->groupQuestionsByDifficulty($allQuestions);

        if ($criteria->shouldBalanceDifficulty()) {
            $selectedQuestions = $this->selectBalancedQuestions(
                $questionsByDifficulty,
                $targetCount,
                $targetDifficulty
            );
        } else {
            $selectedQuestions = $this->selectRandomQuestions($allQuestions, $targetCount);
        }

        // Apply question type distribution if specified
        if (!empty($criteria->getQuestionTypeDistribution())) {
            $selectedQuestions = $this->applyTypeDistribution(
                $selectedQuestions,
                $criteria->getQuestionTypeDistribution()
            );
        }

        return $selectedQuestions;
    }

    /**
     * Group questions by their difficulty level.
     */
    private function groupQuestionsByDifficulty(array $questions): array
    {
        $grouped = [];
        
        foreach ($questions as $question) {
            $level = $question->getDifficultyLevel()->getLevel();
            if (!isset($grouped[$level])) {
                $grouped[$level] = [];
            }
            $grouped[$level][] = $question;
        }

        return $grouped;
    }

    /**
     * Select questions with balanced difficulty distribution.
     */
    private function selectBalancedQuestions(
        array $questionsByDifficulty,
        int $targetCount,
        EnhancedDifficultyLevel $targetDifficulty
    ): array {
        $selected = [];
        $targetLevel = $targetDifficulty->getLevel();

        // Define distribution around target difficulty
        $distribution = $this->calculateDifficultyDistribution($targetLevel, $targetCount);

        foreach ($distribution as $level => $count) {
            if (isset($questionsByDifficulty[$level]) && $count > 0) {
                $availableQuestions = $questionsByDifficulty[$level];
                shuffle($availableQuestions);
                
                $questionsToAdd = array_slice($availableQuestions, 0, $count);
                $selected = array_merge($selected, $questionsToAdd);
            }
        }

        // If we don't have enough questions, add more from available levels
        while (count($selected) < $targetCount) {
            $remainingQuestions = array_merge(...array_values($questionsByDifficulty));
            $remainingQuestions = array_filter($remainingQuestions, function ($question) use ($selected) {
                return !in_array($question, $selected, true);
            });

            if (empty($remainingQuestions)) {
                break;
            }

            shuffle($remainingQuestions);
            $selected[] = $remainingQuestions[0];
        }

        return array_slice($selected, 0, $targetCount);
    }

    /**
     * Calculate difficulty distribution around target level.
     */
    private function calculateDifficultyDistribution(int $targetLevel, int $totalCount): array
    {
        $distribution = [];
        
        // 50% at target level
        $distribution[$targetLevel] = intval($totalCount * 0.5);
        
        // 25% easier
        $easierLevel = max(1, $targetLevel - 1);
        $distribution[$easierLevel] = intval($totalCount * 0.25);
        
        // 25% harder
        $harderLevel = min(10, $targetLevel + 1);
        $distribution[$harderLevel] = intval($totalCount * 0.25);

        // Distribute any remaining questions
        $remaining = $totalCount - array_sum($distribution);
        if ($remaining > 0) {
            $distribution[$targetLevel] += $remaining;
        }

        return array_filter($distribution, fn($count) => $count > 0);
    }

    /**
     * Select random questions up to target count.
     */
    private function selectRandomQuestions(array $questions, int $targetCount): array
    {
        shuffle($questions);
        return array_slice($questions, 0, $targetCount);
    }

    /**
     * Apply question type distribution to selection.
     */
    private function applyTypeDistribution(array $questions, array $typeDistribution): array
    {
        $questionsByType = [];
        
        // Group questions by type
        foreach ($questions as $question) {
            $type = $question->getType()->getValue();
            if (!isset($questionsByType[$type])) {
                $questionsByType[$type] = [];
            }
            $questionsByType[$type][] = $question;
        }

        $selected = [];
        
        // Apply distribution
        foreach ($typeDistribution as $type => $percentage) {
            if (isset($questionsByType[$type])) {
                $count = intval(count($questions) * ($percentage / 100));
                $typeQuestions = array_slice($questionsByType[$type], 0, $count);
                $selected = array_merge($selected, $typeQuestions);
            }
        }

        return $selected;
    }

    /**
     * Extract unique categories from questions.
     */
    private function extractCategoriesFromQuestions(array $questions): array
    {
        $categories = [];
        $categoryIds = [];

        foreach ($questions as $question) {
            $category = $question->getCategory();
            if ($category !== null) {
                $categoryId = $category->getId()?->toString();
                if ($categoryId && !in_array($categoryId, $categoryIds, true)) {
                    $categories[] = $category;
                    $categoryIds[] = $categoryId;
                }
            }
        }

        return $categories;
    }
}