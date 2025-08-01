<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\EnhancedQuestion;
use App\Quiz\Domain\Service\QuizGenerationCriteria;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuestionType;
use App\Quiz\Domain\ValueObject\Tag;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for enhanced questions.
 * Provides advanced querying capabilities for question selection.
 */
interface EnhancedQuestionRepositoryInterface
{
    /**
     * Save a question.
     */
    public function save(EnhancedQuestion $question): void;

    /**
     * Find a question by ID.
     */
    public function findById(Id $id): ?EnhancedQuestion;

    /**
     * Find questions by multiple IDs.
     */
    public function findByIds(array $ids): array;

    /**
     * Find questions by quiz generation criteria.
     */
    public function findByCriteria(QuizGenerationCriteria $criteria): array;

    /**
     * Find questions by difficulty level range.
     */
    public function findByDifficultyRange(
        EnhancedDifficultyLevel $min,
        EnhancedDifficultyLevel $max,
        int $limit = null
    ): array;

    /**
     * Find questions by question type.
     */
    public function findByType(QuestionType $type, int $limit = null): array;

    /**
     * Find questions by category IDs.
     */
    public function findByCategoryIds(array $categoryIds, int $limit = null): array;

    /**
     * Find questions by tags.
     */
    public function findByTags(array $tags, int $limit = null): array;

    /**
     * Find questions with tag matching.
     */
    public function findByTagNames(array $tagNames, int $limit = null): array;

    /**
     * Find random questions matching criteria.
     */
    public function findRandom(int $count, array $excludeIds = []): array;

    /**
     * Count questions by criteria.
     */
    public function countByCriteria(QuizGenerationCriteria $criteria): int;

    /**
     * Find questions that need difficulty recalculation.
     */
    public function findQuestionsNeedingDifficultyUpdate(): array;

    /**
     * Find most frequently failed questions.
     */
    public function findMostFailedQuestions(int $limit = 10): array;

    /**
     * Find questions with highest average response time.
     */
    public function findSlowestQuestions(int $limit = 10): array;

    /**
     * Search questions by content.
     */
    public function searchByContent(string $searchTerm, int $limit = null): array;

    /**
     * Remove a question.
     */
    public function remove(EnhancedQuestion $question): void;

    /**
     * Get next available ID.
     */
    public function nextId(): Id;
}