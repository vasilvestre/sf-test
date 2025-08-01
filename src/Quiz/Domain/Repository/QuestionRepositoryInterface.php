<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\Question;
use App\Shared\Domain\Repository\RepositoryInterface;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for Question aggregate.
 */
interface QuestionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a question by its ID.
     */
    public function findById(Id $id): ?Question;

    /**
     * Find questions by category ID.
     *
     * @return Question[]
     */
    public function findByCategoryId(Id $categoryId): array;

    /**
     * Find random questions from a category.
     *
     * @return Question[]
     */
    public function findRandomByCategoryId(Id $categoryId, int $limit): array;

    /**
     * Find all questions.
     *
     * @return Question[]
     */
    public function findAll(): array;

    /**
     * Save a question.
     */
    public function save(Question $question): void;

    /**
     * Remove a question.
     */
    public function remove(Question $question): void;
}