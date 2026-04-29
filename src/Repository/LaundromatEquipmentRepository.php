<?php

namespace App\Repository;

use App\Entity\Enum\Equipment;
use App\Entity\Laundromat;
use App\Entity\LaundromatEquipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LaundromatEquipment>
 */
class LaundromatEquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LaundromatEquipment::class);
    }

    public function countEquipmentsByType(Laundromat $laundromat): array
    {
        $rows = $this->createQueryBuilder('le')
            ->select('le.type AS type')
            ->addSelect('COUNT(le.id) AS equipmentCount')
            ->andWhere('le.laundromat = :laundromat')
            ->setParameter('laundromat', $laundromat)
            ->groupBy('le.type')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(
            array_map(fn(Equipment $equipment) => $equipment->value, Equipment::cases()),
            0
        );

        foreach ($rows as $row) {
            $type = $row['type'] instanceof Equipment ? $row['type']->value : $row['type'];
            $counts[$type] = (int) $row['equipmentCount'];
        }

        return $counts;
    }
}
