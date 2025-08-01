<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\QuizResult;
use App\Shared\Domain\Repository\RepositoryInterface;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for QuizResult aggregate.
 */
interface QuizResultRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a quiz result by its ID.
     */
    public function findById(Id $id): ?QuizResult;

    /**
     * Find quiz results by category ID.
     *
     * @return QuizResult[]
     */
    public function findByCategoryId(Id $categoryId): array;

    /**
     * Find all quiz results ordered by date.
     *
     * @return QuizResult[]
     */
    public function findAllOrderedByDate(): array;

    /**
     * Get total number of quizzes taken.
     */
    public function getTotalQuizzesTaken(?Id $categoryId = null): int;

    /**
     * Get average success rate.
     */
    public function getAverageSuccessRate(?Id $categoryId = null): float;

    /**
     * Save a quiz result.
     */
    public function save(QuizResult $quizResult): void;
}