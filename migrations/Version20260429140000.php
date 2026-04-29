<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename address.lattitude to latitude and use DECIMAL(10,7) for GPS coordinates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address CHANGE lattitude latitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('ALTER TABLE address MODIFY longitude NUMERIC(10, 7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address CHANGE latitude lattitude NUMERIC(10, 0) DEFAULT NULL');
        $this->addSql('ALTER TABLE address MODIFY longitude NUMERIC(10, 0) DEFAULT NULL');
    }
}
