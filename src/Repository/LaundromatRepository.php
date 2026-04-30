<?php

namespace App\Repository;

use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Laundromat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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

    public function findNearby(
        float $latitude,
        float $longitude,
        int $radiusMeters = 5000,
        int $limit = 50,
        array $filters = [],
    ): array {
        $radiusMeters = max(1, $radiusMeters);
        $limit = max(1, $limit);

        $pointJson = json_encode([
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude],
        ], JSON_THROW_ON_ERROR);

        $sql = 'SELECT l.id, '
            . 'ST_Distance_Sphere(ST_GeomFromGeoJSON(a.position), ST_GeomFromGeoJSON(:point)) AS distance_meters '
            . 'FROM laundromat l '
            . 'INNER JOIN address a ON a.id = l.address_id '
            . 'WHERE l.status = :status '
            . 'AND l.deleted_at IS NULL '
            . 'AND a.position IS NOT NULL '
            . 'AND a.geolocation_status = :geo_status '
            . 'AND ST_Distance_Sphere(ST_GeomFromGeoJSON(a.position), ST_GeomFromGeoJSON(:point)) <= :radius ';

        $params = [
            'point' => $pointJson,
            'radius' => $radiusMeters,
            'status' => LaundromatStatus::Validated->value,
            'geo_status' => GeolocationStatus::Geolocated->value,
        ];

        $types = [
            'point' => ParameterType::STRING,
            'radius' => ParameterType::INTEGER,
            'status' => ParameterType::STRING,
            'geo_status' => ParameterType::STRING,
        ];

        $addFilter = function (string $filterKey, string $paramName, string $subQuery) use (&$sql, &$params, &$types, $filters) {
            $items = $filters[$filterKey] ?? null;
            if ($items === null || $items === '' || $items === []) {
                return;
            }
            if (!\is_array($items)) {
                $items = [$items];
            }
            $validItems = array_filter($items, static fn ($item): bool => \is_string($item) && $item !== '');
            if ($validItems !== []) {
                $sql .= $subQuery;
                $params[$paramName] = array_values($validItems);
                $types[$paramName] = ArrayParameterType::STRING;
            }
        };

        $addFilter('services', 'services', 'AND EXISTS (
            SELECT 1 FROM laundromat_service ls 
            INNER JOIN service s ON s.id = ls.service_id 
            WHERE ls.laundromat_id = l.id AND s.name IN (:services)
        ) ');

        $addFilter('paymentMethods', 'payment_methods', 'AND EXISTS (
            SELECT 1 FROM laundromat_payment_method lpm 
            INNER JOIN payment_method pm ON pm.id = lpm.payment_method_id 
            WHERE lpm.laundromat_id = l.id AND pm.name IN (:payment_methods)
        ) ');

        $addFilter('equipmentTypes', 'equipment_types', 'AND EXISTS (
            SELECT 1 FROM laundromat_equipment le 
            WHERE le.laundromat_id = l.id AND le.type IN (:equipment_types)
        ) ');

        $sql .= 'ORDER BY distance_meters ASC LIMIT '.$limit;

        $distanceRows = $this->getEntityManager()->getConnection()->executeQuery($sql, $params, $types)->fetchAllAssociative();

        if ($distanceRows === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $r): int => (int) ($r['id']), $distanceRows)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('l');
        $qb->leftJoin('l.address', 'a')->addSelect('a')
            ->leftJoin('l.logo', 'logo')->addSelect('logo')
            ->leftJoin('l.closures', 'c')->addSelect('c')
            ->leftJoin('l.equipments', 'e')->addSelect('e')
            ->where($qb->expr()->in('l.id', ':ids'))
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        $loaded = $qb->getQuery()->getResult();
        $byId = [];
        foreach ($loaded as $entity) {
            if ($entity instanceof Laundromat && null !== $entity->getId()) {
                $byId[$entity->getId()] = $entity;
            }
        }

        $out = [];
        foreach ($distanceRows as $r) {
            $id = (int) ($r['id']);
            $distanceMeters = $r['distance_meters'];
            if (!isset($byId[$id])) {
                continue;
            }

            $out[] = [
                'laundromat' => $byId[$id],
                'distanceMeters' => (float) $distanceMeters,
                'averageRating' => null,
            ];
        }

        if ($out !== []) {
            $aggSql = 'SELECT lr.laundromat_id AS id, ROUND(AVG(lr.rating), 2) AS average_rating '
                . 'FROM laundromat_rating lr '
                . 'WHERE lr.laundromat_id IN (:ids) '
                . 'AND lr.rating IS NOT NULL AND lr.rating BETWEEN 0 AND 5 '
                . 'GROUP BY lr.laundromat_id';
            $aggRows = $this->getEntityManager()->getConnection()->executeQuery(
                $aggSql,
                ['ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            )->fetchAllAssociative();

            $ratingById = [];
            foreach ($aggRows as $row) {
                $rid = (int) ($row['id']);
                $avg = $row['average_rating'] ?? null;
                if ($rid > 0 && $avg !== null) {
                    $ratingById[$rid] = (float) $avg;
                }
            }

            foreach ($out as $i => $item) {
                $lid = $item['laundromat']->getId();
                if (null !== $lid && isset($ratingById[$lid])) {
                    $out[$i]['averageRating'] = $ratingById[$lid];
                }
            }
        }

        return $out;
    }
}
