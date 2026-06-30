<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add laundromat_social_link table for external links (website, facebook, instagram, x, linkedin)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS laundromat_social_link (
            id INT AUTO_INCREMENT NOT NULL,
            laundromat_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            url VARCHAR(2048) NOT NULL,
            UNIQUE INDEX uq_laundromat_social_type (laundromat_id, type),
            INDEX IDX_laundromat_social_link (laundromat_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE laundromat_social_link DROP FOREIGN KEY IF EXISTS FK_laundromat_social_link');
        $this->addSql('ALTER TABLE laundromat_social_link ADD CONSTRAINT FK_laundromat_social_link FOREIGN KEY (laundromat_id) REFERENCES laundromat (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat_social_link DROP FOREIGN KEY IF EXISTS FK_laundromat_social_link');
        $this->addSql('DROP TABLE IF EXISTS laundromat_social_link');
    }
}
