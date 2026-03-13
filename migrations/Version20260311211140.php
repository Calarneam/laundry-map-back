<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311211140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE zip_code zip_code INT NOT NULL, CHANGE country country VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE laundromat CHANGE added_date added_date DATE NOT NULL, CHANGE updated_at updated_at DATE NOT NULL, CHANGE deleted_at deleted_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE laundromat_closure CHANGE added_date added_date DATETIME NOT NULL, CHANGE start_time start_time DATETIME NOT NULL, CHANGE end_time end_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE laundromat_equipment CHANGE equipment_reference equipment_reference INT DEFAULT NULL, CHANGE capacity capacity INT NOT NULL, CHANGE duration duration INT NOT NULL');
        $this->addSql('ALTER TABLE laundromat_interaction_history CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE laundromat_rating CHANGE rating rating INT DEFAULT NULL, CHANGE rated_at rated_at DATE DEFAULT NULL, CHANGE commented_at commented_at DATE DEFAULT NULL, CHANGE responded_at responded_at DATE DEFAULT NULL, CHANGE comment_deleted_at comment_deleted_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE media CHANGE size size INT NOT NULL');
        $this->addSql('ALTER TABLE professional CHANGE siren siren INT NOT NULL, CHANGE validation_date validation_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE zip_code zip_code NUMERIC(5, 0) NOT NULL, CHANGE country country VARCHAR(0) NOT NULL');
        $this->addSql('ALTER TABLE laundromat CHANGE added_date added_date DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE laundromat_closure CHANGE added_date added_date DATE NOT NULL, CHANGE start_time start_time TIME NOT NULL, CHANGE end_time end_time TIME NOT NULL');
        $this->addSql('ALTER TABLE laundromat_equipment CHANGE equipment_reference equipment_reference NUMERIC(5, 0) DEFAULT NULL, CHANGE capacity capacity NUMERIC(5, 0) NOT NULL, CHANGE duration duration NUMERIC(5, 0) NOT NULL');
        $this->addSql('ALTER TABLE laundromat_interaction_history CHANGE date date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE laundromat_rating CHANGE rating rating NUMERIC(1, 0) DEFAULT NULL, CHANGE rated_at rated_at DATETIME DEFAULT NULL, CHANGE commented_at commented_at DATETIME DEFAULT NULL, CHANGE responded_at responded_at DATETIME DEFAULT NULL, CHANGE comment_deleted_at comment_deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE media CHANGE size size NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE professional CHANGE siren siren NUMERIC(9, 0) NOT NULL, CHANGE validation_date validation_date DATETIME DEFAULT NULL');
    }
}
