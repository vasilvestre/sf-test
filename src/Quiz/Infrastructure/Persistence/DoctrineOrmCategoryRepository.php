<?php

declare(strict_types=1);

namespace App\Quiz\Infrastructure\Persistence;

use App\Quiz\Domain\Entity\Category;
use App\Quiz\Domain\Repository\CategoryRepositoryInterface;
use App\Shared\Domain\ValueObject\Id;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine ORM implementation of CategoryRepositoryInterface.
 */
final class DoctrineOrmCategoryRepository extends ServiceEntityRepository implements CategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function nextIdentity(): Id
    {
        // This would typically generate a new ID, for now using a placeholder
        return Id::fromInt(random_int(1000, 9999));
    }

    public function findById(Id $id): ?Category
    {
        return $this->find($id->getValue());
    }

    public function findByName(string $name): ?Category
    {
        return $this->findOneBy(['name.value' => $name]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }
}