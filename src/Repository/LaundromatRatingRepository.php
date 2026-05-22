<?php

namespace App\Repository;

use App\Entity\LaundromatRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatRating>
 */
class LaundromatRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatRating::class);
    }

    /**
     * @return array{averageRating: float|null, ratingCount: int}
     */
    public function getAggregatesForLaundromat(int $laundromatId): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating, COUNT(r.id) AS ratingCount')
            ->andWhere('r.laundromat = :laundromatId')
            ->setParameter('laundromatId', $laundromatId)
            ->getQuery()
            ->getSingleResult();

        return [
            'averageRating' => $result['averageRating'] !== null ? round((float) $result['averageRating'], 2) : null,
            'ratingCount' => (int) $result['ratingCount'],
        ];
    }
}
