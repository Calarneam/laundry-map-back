<?php

namespace App\Repository;

use App\Entity\SocialLinkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SocialLinkType>
 */
class SocialLinkTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialLinkType::class);
    }
}
