<?php

declare(strict_types=1);

namespace App\DataFixtures\Purger;

use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Factory pour le purger MySQL (alias "mysql" dans doctrine:fixtures:load --purger=mysql).
 * Utilise le ORMPurger standard.
 */
final class MysqlPurgerFactory implements PurgerFactory
{
    public function createForEntityManager(
        ?string $emName,
        EntityManagerInterface $em,
        array $excluded = [],
        bool $purgeWithTruncate = false,
    ): PurgerInterface {
        $purger = new ORMPurger($em, $excluded);
        $purger->setPurgeMode($purgeWithTruncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

        return $purger;
    }
}
