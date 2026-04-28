<?php

namespace App\Repository;

use App\Entity\Laundromat;
use App\Entity\Enum\LaundromatStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Laundromat>
 */
class LaundromatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Laundromat::class);
    }

    public function countPendingLaundromats(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('(l.status = :status OR l.pendingChanges IS NOT NULL)')
            ->andWhere('l.deletedAt IS NULL')
            ->setParameter('status', LaundromatStatus::Pending)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPendingLaundromats(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('(l.status = :status OR l.pendingChanges IS NOT NULL)')
            ->andWhere('l.deletedAt IS NULL')
            ->join('l.professional', 'p')
            ->join('p.user', 'u')
            ->join('l.address', 'a')
            ->setParameter('status', LaundromatStatus::Pending)
            ->setParameter('newSince', new \DateTimeImmutable('-1 day'))
            ->select(
                'l.id, l.establishmentName, l.addedDate, l.updatedAt, l.contactEmail, l.status, u.firstName, u.lastName, u.email, p.companyName, p.siren, a.street, a.zipCode, a.city, CASE WHEN l.pendingChanges IS NOT NULL THEN true ELSE false END AS hasPendingChanges, CASE WHEN l.status = :status THEN CASE WHEN l.addedDate >= :newSince THEN true ELSE false END ELSE false END AS isNew'
            )
            ->orderBy('l.addedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
