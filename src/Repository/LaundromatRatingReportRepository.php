<?php

namespace App\Repository;

use App\Entity\LaundromatRatingReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatRatingReport>
 */
class LaundromatRatingReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatRatingReport::class);
    }
}
