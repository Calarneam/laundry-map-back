<?php

namespace App\Repository;

use App\Entity\LaundromatInteractionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatInteractionHistory>
 */
class LaundromatInteractionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatInteractionHistory::class);
    }
}
