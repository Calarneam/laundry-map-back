<?php

namespace App\DataFixtures\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;

final class MysqlPurger implements ORMPurgerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private readonly array $excluded = [],
        private readonly bool $purgeWithTruncate = false
    ) {
    }

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    public function purge(): void
    {
        $conn = $this->em->getConnection();
        $isMysql = $conn->getDatabasePlatform() instanceof AbstractMySQLPlatform;

        if ($isMysql) {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        }

        try {
            $purger = new ORMPurger($this->em, $this->excluded);
            $purger->setPurgeMode(
                $this->purgeWithTruncate
                    ? ORMPurger::PURGE_MODE_TRUNCATE
                    : ORMPurger::PURGE_MODE_DELETE
            );
            $purger->purge();
        } finally {
            if ($isMysql) {
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            }
        }
    }
}
