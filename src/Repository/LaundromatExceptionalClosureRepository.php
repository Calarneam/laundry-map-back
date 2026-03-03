<?php

namespace App\Repository;

use App\Entity\LaundromatExceptionalClosure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatExceptionalClosure>
 */
class LaundromatExceptionalClosureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatExceptionalClosure::class);
    }
}
