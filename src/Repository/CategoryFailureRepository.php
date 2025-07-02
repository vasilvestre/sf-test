<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\CategoryFailure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryFailure>
 *
 * @method CategoryFailure|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryFailure|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryFailure[]    findAll()
 * @method CategoryFailure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryFailureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryFailure::class);
    }

    /**
     * Find a CategoryFailure entity by Category
     */
    public function findByCategory(Category $category): ?CategoryFailure
    {
        return $this->findOneBy(['category' => $category]);
    }

    /**
     * Find the most failed categories
     */
    public function findMostFailedCategories(int $limit = 5): array
    {
        return $this->createQueryBuilder('cf')
            ->select('cf', 'c')
            ->join('cf.category', 'c')
            ->orderBy('cf.failureCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
