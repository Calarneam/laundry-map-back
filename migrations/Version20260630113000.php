<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create web_link table for laundromat external links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE web_link (laundromat_id INT NOT NULL, type VARCHAR(32) NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_31518777AD7D929 (laundromat_id), PRIMARY KEY(laundromat_id, type)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE web_link ADD CONSTRAINT FK_31518777AD7D929 FOREIGN KEY (laundromat_id) REFERENCES laundromat (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE web_link DROP FOREIGN KEY FK_31518777AD7D929');
        $this->addSql('DROP TABLE web_link');
    }
}
