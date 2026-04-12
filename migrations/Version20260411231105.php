<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411231105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE geolocation_status geolocation_status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE laundromat DROP FOREIGN KEY `FK_44C31F25DB77003`');
        $this->addSql('ALTER TABLE laundromat CHANGE status status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE laundromat ADD CONSTRAINT FK_44C31F25DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE laundromat_interaction_history DROP FOREIGN KEY `FK_8A054AB297F0DCEB`');
        $this->addSql('ALTER TABLE laundromat_interaction_history ADD CONSTRAINT FK_8A054AB297F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY `FK_DF9658F5A32EFC6`');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY `FK_DF9658F5A76ED395`');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT FK_DF9658F5A32EFC6 FOREIGN KEY (rating_id) REFERENCES laundromat_rating (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT FK_DF9658F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professional_interaction_history DROP FOREIGN KEY `FK_2F90A1F9DB77003`');
        $this->addSql('ALTER TABLE professional_interaction_history ADD CONSTRAINT FK_2F90A1F9DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE last_name last_name VARCHAR(50) DEFAULT NULL, CHANGE first_name first_name VARCHAR(50) DEFAULT NULL, CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_interaction_history DROP FOREIGN KEY `FK_6D3F2858A76ED395`');
        $this->addSql('ALTER TABLE user_interaction_history ADD CONSTRAINT FK_6D3F2858A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE geolocation_status geolocation_status VARCHAR(50) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE laundromat DROP FOREIGN KEY FK_44C31F25DB77003');
        $this->addSql('ALTER TABLE laundromat CHANGE status status VARCHAR(50) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE laundromat ADD CONSTRAINT `FK_44C31F25DB77003` FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE laundromat_interaction_history DROP FOREIGN KEY FK_8A054AB297F0DCEB');
        $this->addSql('ALTER TABLE laundromat_interaction_history ADD CONSTRAINT `FK_8A054AB297F0DCEB` FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY FK_DF9658F5A32EFC6');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY FK_DF9658F5A76ED395');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT `FK_DF9658F5A32EFC6` FOREIGN KEY (rating_id) REFERENCES laundromat_rating (id)');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT `FK_DF9658F5A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE professional_interaction_history DROP FOREIGN KEY FK_2F90A1F9DB77003');
        $this->addSql('ALTER TABLE professional_interaction_history ADD CONSTRAINT `FK_2F90A1F9DB77003` FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE user CHANGE last_name last_name VARCHAR(255) DEFAULT NULL, CHANGE first_name first_name VARCHAR(255) DEFAULT NULL, CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_interaction_history DROP FOREIGN KEY FK_6D3F2858A76ED395');
        $this->addSql('ALTER TABLE user_interaction_history ADD CONSTRAINT `FK_6D3F2858A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
