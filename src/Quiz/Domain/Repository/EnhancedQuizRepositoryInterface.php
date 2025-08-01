<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\EnhancedQuiz;
use App\Quiz\Domain\ValueObject\EnhancedDifficultyLevel;
use App\Quiz\Domain\ValueObject\QuizTemplate;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for enhanced quizzes.
 * Provides comprehensive quiz querying and management.
 */
interface EnhancedQuizRepositoryInterface
{
    /**
     * Save a quiz.
     */
    public function save(EnhancedQuiz $quiz): void;

    /**
     * Find a quiz by ID.
     */
    public function findById(Id $id): ?EnhancedQuiz;

    /**
     * Find all published quizzes.
     */
    public function findPublished(): array;

    /**
     * Find quizzes by template type.
     */
    public function findByTemplate(QuizTemplate $template): array;

    /**
     * Find quizzes by difficulty range.
     */
    public function findByDifficultyRange(
        EnhancedDifficultyLevel $min,
        EnhancedDifficultyLevel $max
    ): array;

    /**
     * Find quizzes by category IDs.
     */
    public function findByCategoryIds(array $categoryIds): array;

    /**
     * Find quizzes with specific tags.
     */
    public function findByTags(array $tagNames): array;

    /**
     * Find popular quizzes based on attempt count.
     */
    public function findPopular(int $limit = 10): array;

    /**
     * Find recently created quizzes.
     */
    public function findRecent(int $limit = 10): array;

    /**
     * Search quizzes by title or description.
     */
    public function searchByTitle(string $searchTerm): array;

    /**
     * Find quizzes suitable for a user's skill level.
     */
    public function findSuitableForUser(string $userId, int $limit = null): array;

    /**
     * Find quizzes that need review (outdated, poor performance).
     */
    public function findNeedingReview(): array;

    /**
     * Remove a quiz.
     */
    public function remove(EnhancedQuiz $quiz): void;

    /**
     * Get next available ID.
     */
    public function nextId(): Id;
}