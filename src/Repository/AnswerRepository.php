<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Answer>
 *
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    /**
     * Find answers by question
     */
    public function findByQuestion(Question $question): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.question = :question')
            ->setParameter('question', $question)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find correct answers by question
     */
    public function findCorrectByQuestion(Question $question): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.question = :question')
            ->andWhere('a.isCorrect = :isCorrect')
            ->setParameter('question', $question)
            ->setParameter('isCorrect', true)
            ->getQuery()
            ->getResult();
    }
}
