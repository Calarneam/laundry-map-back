<?php

namespace App\Repository;

use App\Entity\LaundromatExceptionalClosureSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatExceptionalClosureSlot>
 */
class LaundromatExceptionalClosureSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatExceptionalClosureSlot::class);
    }
}
