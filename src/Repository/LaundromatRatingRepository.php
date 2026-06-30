<?php

namespace App\Repository;

use App\Entity\Laundromat;
use App\Entity\LaundromatRating;
use App\Entity\User;
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

    public function findByLaundromat(Laundromat $laundromat, int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->where('r.laundromat = :laundromat')
            ->andWhere('r.commentDeletedAt IS NULL')
            ->setParameter('laundromat', $laundromat)
            ->orderBy('CASE WHEN r.commentedAt IS NOT NULL THEN r.commentedAt ELSE r.ratedAt END', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByLaundromat(Laundromat $laundromat): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.laundromat = :laundromat')
            ->andWhere('r.commentDeletedAt IS NULL')
            ->setParameter('laundromat', $laundromat)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.laundromat', 'l')
            ->addSelect('l')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('CASE WHEN r.commentedAt IS NOT NULL THEN r.commentedAt ELSE r.ratedAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByLaundromatAndUser(Laundromat $laundromat, User $user): ?LaundromatRating
    {
        return $this->findOneBy(['laundromat' => $laundromat, 'user' => $user]);
    }

    /**
     * @return array{averageRating: float|null, ratingCount: int}
     */
    public function getAggregatesForLaundromat(int $laundromatId): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating, COUNT(r.id) AS ratingCount')
            ->andWhere('r.laundromat = :laundromatId')
            ->andWhere('r.commentDeletedAt IS NULL')
            ->setParameter('laundromatId', $laundromatId)
            ->getQuery()
            ->getSingleResult();

        return [
            'averageRating' => $result['averageRating'] !== null ? round((float) $result['averageRating'], 2) : null,
            'ratingCount' => (int) $result['ratingCount'],
        ];
    }
}
