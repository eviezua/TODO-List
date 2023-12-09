<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }
    public function findTasksByFilters(
        int $userId,
        ?string $status = null,
        ?int $priority = null,
        ?string $search = null,
        ?string $createdAt = null,
        ?string $completedAt = null,
        ?string $orderBy = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :userId')
            ->setParameter('userId', $userId);
        if ($status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }
        if ($priority) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $priority);
        }
        if ($search) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('t.title', ':search'),
                $qb->expr()->like('t.description', ':search')
            ))
                ->setParameter('search', '%' . $search . '%');
        }
        if ($createdAt) {
            $qb->andWhere('t.createdAt >= :createdAt AND t.createdAt < :nextDay')
                ->setParameter('createdAt', new \DateTime($createdAt))
                ->setParameter('nextDay', new \DateTime($createdAt . ' +1 day'));
        }
        if ($completedAt) {
            $qb->andWhere('t.completedAt >= :completedAt AND t.completedAt < :nextDay')
                ->setParameter('completedAt', new \DateTime($completedAt))
                ->setParameter('nextDay', new \DateTime($completedAt . ' +1 day'));
        }
        if ($orderBy) {
            [$field, $direction] = explode(' ', $orderBy);

            if ($direction === 'asc' || $direction === 'desc') {
                $qb->orderBy('t.' . $field, $direction);
            } else {
                throw new \InvalidArgumentException('Invalid sorting direction. Use "asc" or "desc".');
            }
        }

        return $qb->getQuery()->getResult();
    }
//    /**
//     * @return Task[] Returns an array of Task objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Task
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
