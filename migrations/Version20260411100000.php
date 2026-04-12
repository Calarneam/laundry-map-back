<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to laundromat table and make logo_id nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE laundromat ADD status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        $this->addSql('ALTER TABLE laundromat MODIFY logo_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat DROP COLUMN status');
        $this->addSql('ALTER TABLE laundromat MODIFY logo_id INT NOT NULL');
    }
}
