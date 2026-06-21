<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * @return list<string>
     */
    public function findAllNames(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.name')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @param list<string> $names
     *
     * @return array<string, Service>
     */
    public function findIndexedByNames(array $names): array
    {
        $normalizedNames = array_values(array_unique(array_filter(
            array_map(static fn (mixed $name): string => trim((string) $name), $names),
            static fn (string $name): bool => $name !== '',
        )));

        if ($normalizedNames === []) {
            return [];
        }

        $services = $this->createQueryBuilder('s')
            ->andWhere('s.name IN (:names)')
            ->setParameter('names', $normalizedNames)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($services as $service) {
            if ($service instanceof Service) {
                $indexed[$service->getName()] = $service;
            }
        }

        return $indexed;
    }
}
