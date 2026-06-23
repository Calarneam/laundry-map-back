<?php

namespace App\Repository;

use App\Entity\LaundromatRating;
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

    public function countOpenReports(): int
    {
        return (int) $this->createQueryBuilder('report')
            ->select('COUNT(report.rating)')
            ->join('report.rating', 'rating')
            ->andWhere('rating.commentDeletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findGroupedOpenReports(): array
    {
        $rows = $this->createQueryBuilder('report')
            ->select(
                'rating.id AS ratingId',
                'rating.comment AS ratingComment',
                'rating.rating AS ratingScore',
                'author.id AS authorId',
                'author.firstName AS authorFirstName',
                'author.lastName AS authorLastName',
                'author.email AS authorEmail',
                'laundromat.id AS laundromatId',
                'laundromat.establishmentName AS laundromatName',
                'MAX(report.reason) AS reportReason',
                'COUNT(report.id) AS reportCount',
                'MAX(report.date) AS createdAt',
            )
            ->join('report.rating', 'rating')
            ->join('rating.user', 'author')
            ->join('rating.laundromat', 'laundromat')
            ->andWhere('rating.commentDeletedAt IS NULL')
            ->groupBy(
                'rating.id',
                'rating.comment',
                'rating.rating',
                'author.id',
                'author.firstName',
                'author.lastName',
                'author.email',
                'laundromat.id',
                'laundromat.establishmentName',
            )
            ->orderBy('COUNT(report.id)', 'DESC')
            ->addOrderBy('MAX(report.date)', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $grouped = [];
        foreach ($rows as $row) {
            $ratingId = (int) $row['ratingId'];
            $grouped[] = [
                'id' => $ratingId,
                'rating' => [
                    'id' => $ratingId,
                    'comment' => $row['ratingComment'],
                    'score' => $row['ratingScore'],
                    'author' => [
                        'id' => (int) $row['authorId'],
                        'firstName' => $row['authorFirstName'],
                        'lastName' => $row['authorLastName'],
                        'email' => $row['authorEmail'],
                    ],
                    'laundry' => [
                        'id' => (int) $row['laundromatId'],
                        'name' => $row['laundromatName'],
                    ],
                ],
                'reason' => $row['reportReason'],
                'reportCount' => (int) $row['reportCount'],
                'createdAt' => $row['createdAt'],
            ];
        }

        return $grouped;
    }

    public function deleteByRating(LaundromatRating $rating): void
    {
        $this->createQueryBuilder('report')
            ->delete()
            ->andWhere('report.rating = :rating')
            ->setParameter('rating', $rating)
            ->getQuery()
            ->execute();
    }
}
