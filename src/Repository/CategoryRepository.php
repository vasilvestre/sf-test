<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Find all categories with their questions and answers
     */
    public function findAllWithQuestionsAndAnswers(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.questions', 'q')
            ->leftJoin('q.answers', 'a')
            ->addSelect('q', 'a')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a category by name with its questions and answers
     */
    public function findOneByNameWithQuestionsAndAnswers(string $name): ?Category
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.questions', 'q')
            ->leftJoin('q.answers', 'a')
            ->addSelect('q', 'a')
            ->where('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
