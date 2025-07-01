<?php

namespace App\Repository;

use App\Entity\QuizResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizResult>
 *
 * @method QuizResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuizResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuizResult[]    findAll()
 * @method QuizResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuizResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizResult::class);
    }

    /**
     * Find all results ordered by creation date (newest first)
     *
     * @param int $limit Optional limit
     * @return QuizResult[]
     */
    public function findAllOrderedByDate(int $limit = null): array
    {
        return $this->createQueryBuilder('qr')
            ->orderBy('qr.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find results for a specific category ordered by creation date
     *
     * @param int $categoryId
     * @param int $limit Optional limit
     * @return QuizResult[]
     */
    public function findByCategoryOrderedByDate(int $categoryId, int $limit = null): array
    {
        return $this->createQueryBuilder('qr')
            ->andWhere('qr.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('qr.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get data for trend chart (last 10 results)
     *
     * @param int|null $categoryId Optional category filter
     * @return array
     */
    public function getChartData(?int $categoryId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('qr')
            ->select('qr.createdAt, qr.score')
            ->orderBy('qr.createdAt', 'ASC')
            ->setMaxResults(10);

        if ($categoryId) {
            $queryBuilder->andWhere('qr.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get total number of quizzes taken
     *
     * @param int|null $categoryId Optional category filter
     * @return int
     */
    public function getTotalQuizzesTaken(?int $categoryId = null): int
    {
        $queryBuilder = $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)');

        if ($categoryId) {
            $queryBuilder->andWhere('qr.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Get average success rate (percentage)
     *
     * @param int|null $categoryId Optional category filter
     * @return float
     */
    public function getAverageSuccessRate(?int $categoryId = null): float
    {
        $queryBuilder = $this->createQueryBuilder('qr')
            ->select('AVG(qr.score)');

        if ($categoryId) {
            $queryBuilder->andWhere('qr.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        $result = $queryBuilder->getQuery()->getSingleScalarResult();
        return $result !== null ? (float)$result : 0.0;
    }
}
