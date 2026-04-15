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

    public function findPendingLaundromats(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.deletedAt IS NULL')
            ->join('l.professional', 'p')
            ->join('p.user', 'u')
            ->join('l.address', 'a')
            ->setParameter('status', LaundromatStatus::Pending)
            ->select('l.id, l.establishmentName, l.description, l.addedDate, l.updatedAt, p.companyName, u.firstName, u.lastName, u.email, a.street, a.zipCode, a.city, l.status')
            ->orderBy('l.addedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }   

    //    /**
    //     * @return Laundromat[] Returns an array of Laundromat objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Laundromat
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
