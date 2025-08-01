<?php

declare(strict_types=1);

namespace App\Quiz\Domain\Repository;

use App\Quiz\Domain\Entity\Category;
use App\Shared\Domain\Repository\RepositoryInterface;
use App\Shared\Domain\ValueObject\Id;

/**
 * Repository interface for Category aggregate.
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a category by its ID.
     */
    public function findById(Id $id): ?Category;

    /**
     * Find a category by its name.
     */
    public function findByName(string $name): ?Category;

    /**
     * Find all categories.
     *
     * @return Category[]
     */
    public function findAll(): array;

    /**
     * Save a category.
     */
    public function save(Category $category): void;

    /**
     * Remove a category.
     */
    public function remove(Category $category): void;
}