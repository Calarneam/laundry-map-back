<?php

namespace App\Repository;

use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Laundromat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
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

    /**
     * Favoris sans calcul de distance : même forme JSON que {@see findFavorites} (sans distanceMeters).
     * Inclut toutes les laveries favorites validées, même si l’adresse n’est pas géolocalisée.
     */
    public function findFavoritesWithoutCoordinates(User $user, ?string $search = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sqlLaundromats = '
        SELECT
            l.id,
            l.establishment_name AS name,
            l.wi_line_reference
        FROM laundromat l
        INNER JOIN user_favorite_laundromat ufl ON ufl.laundromat_id = l.id
        WHERE ufl.user_id = :user_id
        AND l.status = :status
        AND l.deleted_at IS NULL';

        $params = [
            'user_id' => $user->getId(),
            'status' => LaundromatStatus::Validated->value,
        ];

        if ($search !== null && $search !== '') {
            $sqlLaundromats .= ' AND l.establishment_name LIKE :search';
            $params['search'] = '%' . $this->escapeLikePattern($search) . '%';
        }

        $sqlLaundromats .= ' ORDER BY l.establishment_name ASC';

        $laundromatRows = $conn->executeQuery($sqlLaundromats, $params)->fetchAllAssociative();

        if ($laundromatRows === []) {
            return [];
        }

        $out = [];
        $laundromatIds = [];

        foreach ($laundromatRows as $row) {
            $id = $row['id'];
            $laundromatIds[] = $id;

            $out[$id] = [
                'id' => $id,
                'name' => $row['name'],
                'medias' => [],
                'services' => [],
                'isWiLineReference' => $row['wi_line_reference'] !== null,
            ];
        }

        $this->appendFavoriteAggregates($conn, $out, $laundromatIds);

        return array_values($out);
    }

    public function findFavorites(User $user, float $latitude, float $longitude, ?string $search = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $pointJson = json_encode([
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude],
        ], JSON_THROW_ON_ERROR);

        $sqlLaundromats = '
        SELECT 
            l.id, 
            l.establishment_name AS name,
            l.wi_line_reference,
            ST_Distance_Sphere(ST_GeomFromGeoJSON(a.position), ST_GeomFromGeoJSON(:point)) AS distanceMeters
        FROM laundromat l
        INNER JOIN user_favorite_laundromat ufl ON ufl.laundromat_id = l.id
        INNER JOIN address a ON a.id = l.address_id
        WHERE ufl.user_id = :user_id
        AND l.status = :status
        AND l.deleted_at IS NULL
        AND a.position IS NOT NULL
        AND a.geolocation_status = :geo_status';

        $params = [
            'point' => $pointJson,
            'user_id' => $user->getId(),
            'status' => LaundromatStatus::Validated->value,
            'geo_status' => GeolocationStatus::Geolocated->value,
        ];

        if ($search !== null && $search !== '') {
            $sqlLaundromats .= ' AND l.establishment_name LIKE :search';
            $params['search'] = '%' . $this->escapeLikePattern($search) . '%';
        }

        $sqlLaundromats .= ' ORDER BY distanceMeters ASC';

        $laundromatRows = $conn->executeQuery($sqlLaundromats, $params)->fetchAllAssociative();

        if ($laundromatRows === []) {
            return [];
        }

        $out = [];
        $laundromatIds = [];

        foreach ($laundromatRows as $row) {
            $id = $row['id'];
            $laundromatIds[] = $id;

            $out[$id] = [
                'id' => $id,
                'name' => $row['name'],
                'medias' => [],
                'services' => [],
                'isWiLineReference' => $row['wi_line_reference'] !== null,
                'distanceMeters' => round((float) $row['distanceMeters'], 2),
            ];
        }

        $this->appendFavoriteAggregates($conn, $out, $laundromatIds);

        return array_values($out);
    }

    /**
     * Échappe les caractères spéciaux LIKE (%, _, \) pour éviter qu'un utilisateur
     * tape un wildcard et déclenche une recherche non voulue.
     */
    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param array<int|string, array<string, mixed>> $out
     * @param list<int|string>                          $laundromatIds
     */
    private function appendFavoriteAggregates(Connection $conn, array &$out, array $laundromatIds): void
    {
        $sqlMedias = '
        SELECT lm.laundromat_id, m.id, m.location AS url, m.original_name AS name, lm.description
        FROM laundromat_media lm
        INNER JOIN media m ON m.id = lm.media_id
        WHERE lm.laundromat_id IN (?)';

        $mediaRows = $conn->executeQuery($sqlMedias, [$laundromatIds], [ArrayParameterType::INTEGER])->fetchAllAssociative();
        foreach ($mediaRows as $row) {
            $out[$row['laundromat_id']]['medias'][] = [
                'id' => $row['id'],
                'url' => $row['url'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        $sqlServices = '
        SELECT ls.laundromat_id, s.name
        FROM laundromat_service ls
        INNER JOIN service s ON s.id = ls.service_id
        WHERE ls.laundromat_id IN (?)';

        $serviceRows = $conn->executeQuery($sqlServices, [$laundromatIds], [ArrayParameterType::INTEGER])->fetchAllAssociative();
        foreach ($serviceRows as $row) {
            $out[$row['laundromat_id']]['services'][] = $row['name'];
        }

        $sqlEquipments = '
        SELECT laundromat_id, type, COUNT(id) as count
        FROM laundromat_equipment
        WHERE laundromat_id IN (?)
        GROUP BY laundromat_id, type';

        $equipmentRows = $conn->executeQuery($sqlEquipments, [$laundromatIds], [ArrayParameterType::INTEGER])->fetchAllAssociative();
        foreach ($equipmentRows as $row) {
            $laundromatId = $row['laundromat_id'];
            $type = strtolower((string) $row['type']);
            $out[$laundromatId]['equipments'][$type] = (int) $row['count'];
        }
    }
}
