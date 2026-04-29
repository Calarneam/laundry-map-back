<?php

namespace App\Repository;

use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Laundromat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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

        $entityManager = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($entityManager);
        $rsm->addRootEntityFromClassMetadata(Laundromat::class, 'l');
        $rsm->addScalarResult('distance_meters', 'distanceMeters', 'float');

        $sql = 'SELECT '
            . $rsm->generateSelectClause(['l' => 'l'])
            . ', ST_Distance_Sphere(POINT(a.longitude, a.lattitude), POINT(:lng, :lat)) AS distance_meters '
            . 'FROM laundromat l '
            . 'INNER JOIN address a ON a.id = l.address_id '
            . 'WHERE l.status = :status '
            . 'AND l.deleted_at IS NULL '
            . 'AND a.lattitude IS NOT NULL '
            . 'AND a.longitude IS NOT NULL '
            . 'AND a.geolocation_status = :geo_status '
            . 'AND ST_Distance_Sphere(POINT(a.longitude, a.lattitude), POINT(:lng, :lat)) <= :radius ';

        $params = [
            'lat' => (string) $latitude,
            'lng' => (string) $longitude,
            'radius' => $radiusMeters,
            'status' => LaundromatStatus::Validated->value,
            'geo_status' => GeolocationStatus::Geolocated->value,
        ];

        $types = [
            'lat' => ParameterType::STRING,
            'lng' => ParameterType::STRING,
            'radius' => ParameterType::INTEGER,
            'status' => ParameterType::STRING,
            'geo_status' => ParameterType::STRING,
        ];

        $addFilter = function (string $filterKey, string $paramName, string $subQuery) use (&$sql, &$params, &$types, $filters) {
            $items = $filters[$filterKey] ?? [];
            if (is_array($items)) {
                $validItems = array_filter($items, fn($item) => is_string($item) && $item !== '');
                if (!empty($validItems)) {
                    $sql .= $subQuery;
                    $params[$paramName] = array_values($validItems);
                    $types[$paramName] = ArrayParameterType::STRING;
                }
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

        $sql .= 'ORDER BY distance_meters ASC LIMIT ' . $limit;

        $query = $entityManager->createNativeQuery($sql, $rsm);
        $query->setParameters($params);

        foreach ($types as $name => $type) {
            $query->setParameter($name, $params[$name], $type);
        }

        $rows = $query->getResult();
        $out = [];

        foreach ($rows as $row) {
            $laundromat = $row[0] ?? null;
            
            if ($laundromat instanceof Laundromat) {
                $out[] = [
                    'laundromat' => $laundromat,
                    'distanceMeters' => (float) ($row['distanceMeters'] ?? 0.0),
                ];
            }
        }

        return $out;
    }
}
