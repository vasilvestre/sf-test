<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\Id as DomainId;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine implementation of UserRepositoryInterface.
 */
final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(DomainId $id): User
    {
        $user = $this->find($id->getValue());
        
        if ($user === null) {
            throw UserNotFoundException::withId($id->getValue());
        }

        return $user;
    }

    public function findByEmail(Email $email): User
    {
        $user = $this->findOneBy(['email' => $email->getValue()]);
        
        if ($user === null) {
            throw UserNotFoundException::withEmail($email->getValue());
        }

        return $user;
    }

    public function findByUsername(Username $username): User
    {
        $user = $this->findOneBy(['username' => $username->getValue()]);
        
        if ($user === null) {
            throw UserNotFoundException::withUsername($username->getValue());
        }

        return $user;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findOneBy(['email' => $email->getValue()]) !== null;
    }

    public function existsByUsername(Username $username): bool
    {
        return $this->findOneBy(['username' => $username->getValue()]) !== null;
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            return (int) $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return parent::count($criteria);
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function findByRole(string $role, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUnverifiedOlderThan(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.emailVerified = false')
            ->andWhere('u.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function nextIdentity(): mixed
    {
        // Generate a new ID for the next User entity
        // In a real application, this could use a proper ID generation strategy
        return DomainId::fromInt(random_int(1000000, 9999999));
    }
}