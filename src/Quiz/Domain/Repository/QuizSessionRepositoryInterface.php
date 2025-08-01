<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\QuizSession;
use App\Shared\Domain\ValueObject\Id;
use App\User\Domain\Entity\UserId;

/**
 * Repository interface for Quiz Session aggregate.
 * Handles persistence and retrieval of quiz session data.
 */
interface QuizSessionRepositoryInterface
{
    /**
     * Find quiz session by ID.
     */
    public function findById(Id $id): ?QuizSession;

    /**
     * Find active session for a user.
     */
    public function findActiveByUserId(UserId $userId): ?QuizSession;

    /**
     * Find sessions by user ID with optional filters.
     */
    public function findByUserId(
        UserId $userId,
        ?bool $isCompleted = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Find sessions by user and date range.
     */
    public function findByUserAndDateRange(
        UserId $userId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate
    ): array;

    /**
     * Save quiz session.
     */
    public function save(QuizSession $session): void;

    /**
     * Delete quiz session.
     */
    public function delete(QuizSession $session): void;

    /**
     * Get user's performance statistics.
     */
    public function getUserPerformanceStats(UserId $userId): array;

    /**
     * Get user's learning analytics data.
     */
    public function getUserLearningAnalytics(
        UserId $userId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null
    ): array;

    /**
     * Get adaptive learning data for recommendations.
     */
    public function getAdaptiveLearningData(
        UserId $userId,
        ?int $categoryId = null,
        int $limit = 100
    ): array;

    /**
     * Get session progress analytics.
     */
    public function getSessionProgressAnalytics(Id $sessionId): array;

    /**
     * Find similar users for comparison (collaborative filtering).
     */
    public function findSimilarUsers(UserId $userId, int $limit = 10): array;
}