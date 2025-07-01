<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Find all questions with their answers
     */
    public function findAllWithAnswers(): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.answers', 'a')
            ->addSelect('a')
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find questions by category with their answers
     */
    public function findByCategoryWithAnswers(Category $category): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.answers', 'a')
            ->addSelect('a')
            ->where('q.category = :category')
            ->setParameter('category', $category)
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
