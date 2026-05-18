<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429165000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change laundromat wi_line_reference to string serial';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat CHANGE wi_line_reference wi_line_reference VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat CHANGE wi_line_reference wi_line_reference INT DEFAULT NULL');
    }
}
