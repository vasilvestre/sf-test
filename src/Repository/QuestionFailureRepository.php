<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Question;
use App\Entity\QuestionFailure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionFailure>
 *
 * @method QuestionFailure|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuestionFailure|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuestionFailure[]    findAll()
 * @method QuestionFailure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionFailureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionFailure::class);
    }

    /**
     * Find a question failure record by question
     */
    public function findByQuestion(Question $question): ?QuestionFailure
    {
        return $this->findOneBy(['question' => $question]);
    }

    /**
     * Get the most failed questions
     */
    public function findMostFailedQuestions(int $limit = 10, ?Category $category = null): array
    {
        $queryBuilder = $this->createQueryBuilder('qf')
            ->select('qf, q')
            ->join('qf.question', 'q')
            ->orderBy('qf.failureCount', 'DESC')
            ->setMaxResults($limit);

        if ($category) {
            $queryBuilder->andWhere('q.category = :category')
                ->setParameter('category', $category);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the total number of failures for all questions
     */
    public function getTotalFailures(?Category $category = null): int
    {
        $queryBuilder = $this->createQueryBuilder('qf')
            ->select('SUM(qf.failureCount)');

        if ($category) {
            $queryBuilder->join('qf.question', 'q')
                ->andWhere('q.category = :category')
                ->setParameter('category', $category);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }
}
