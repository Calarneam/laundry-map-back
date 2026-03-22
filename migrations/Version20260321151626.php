<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321151626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD geolocation_status VARCHAR(50) NOT NULL DEFAULT \'pending\', CHANGE lattitude lattitude NUMERIC(10, 0) DEFAULT NULL, CHANGE longitude longitude NUMERIC(10, 0) DEFAULT NULL');
        $this->addSql('ALTER TABLE professional CHANGE company_name company_name VARCHAR(255) NOT NULL, CHANGE code_ape code_ape VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP geolocation_status, CHANGE lattitude lattitude NUMERIC(10, 0) NOT NULL, CHANGE longitude longitude NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE professional CHANGE company_name company_name VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE code_ape code_ape VARCHAR(10) DEFAULT \'\' NOT NULL');
    }
}
