<?php

namespace App\Repository;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentMethod>
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    /**
     * @return list<string>
     */
    public function findAllNames(): array
    {
        return $this->createQueryBuilder('pm')
            ->select('pm.name')
            ->orderBy('pm.name', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @param list<string> $names
     *
     * @return array<string, PaymentMethod>
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

        $paymentMethods = $this->createQueryBuilder('pm')
            ->andWhere('pm.name IN (:names)')
            ->setParameter('names', $normalizedNames)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod instanceof PaymentMethod) {
                $indexed[$paymentMethod->getName()] = $paymentMethod;
            }
        }

        return $indexed;
    }
}
