<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\EnhancedQuizAttempt;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for enhanced quiz attempts.
 * Manages quiz attempt persistence and querying.
 */
interface EnhancedQuizAttemptRepositoryInterface
{
    /**
     * Save a quiz attempt.
     */
    public function save(EnhancedQuizAttempt $attempt): void;

    /**
     * Find an attempt by ID.
     */
    public function findById(Id $id): ?EnhancedQuizAttempt;

    /**
     * Find attempts by user ID.
     */
    public function findByUserId(Id $userId): array;

    /**
     * Find attempts by quiz ID.
     */
    public function findByQuizId(Id $quizId): array;

    /**
     * Find attempts by user and quiz.
     */
    public function findByUserAndQuiz(Id $userId, Id $quizId): array;

    /**
     * Find user's latest attempt for a quiz.
     */
    public function findLatestAttemptByUserAndQuiz(Id $userId, Id $quizId): ?EnhancedQuizAttempt;

    /**
     * Find user's best attempt for a quiz.
     */
    public function findBestAttemptByUserAndQuiz(Id $userId, Id $quizId): ?EnhancedQuizAttempt;

    /**
     * Count attempts by user for a quiz.
     */
    public function countAttemptsByUserAndQuiz(Id $userId, Id $quizId): int;

    /**
     * Find recent attempts for performance analysis.
     */
    public function findRecentAttempts(int $days = 30, int $limit = null): array;

    /**
     * Find attempts with specific status.
     */
    public function findByStatus(string $status): array;

    /**
     * Find abandoned attempts that can be resumed.
     */
    public function findResumableAttempts(Id $userId): array;

    /**
     * Find attempts for a specific question.
     */
    public function findAttemptsForQuestion(Id $questionId): array;

    /**
     * Remove an attempt.
     */
    public function remove(EnhancedQuizAttempt $attempt): void;

    /**
     * Get next available ID.
     */
    public function nextId(): Id;
}