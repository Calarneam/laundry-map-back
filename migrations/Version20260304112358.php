<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304112358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, zip_code NUMERIC(5, 0) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(0) NOT NULL, lattitude NUMERIC(10, 0) NOT NULL, longitude NUMERIC(10, 0) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE administrator (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat (id INT AUTO_INCREMENT NOT NULL, wi_line_reference INT DEFAULT NULL, establishment_name VARCHAR(255) NOT NULL, contact_email VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, added_date DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, professional_id INT NOT NULL, address_id INT NOT NULL, logo_id INT NOT NULL, INDEX IDX_44C31F25DB77003 (professional_id), UNIQUE INDEX UNIQ_44C31F25F5B7AF75 (address_id), UNIQUE INDEX UNIQ_44C31F25F98F144A (logo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_service (laundromat_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_8FDA692997F0DCEB (laundromat_id), INDEX IDX_8FDA6929ED5CA9E6 (service_id), PRIMARY KEY (laundromat_id, service_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_payment_method (laundromat_id INT NOT NULL, payment_method_id INT NOT NULL, INDEX IDX_BF34A00B97F0DCEB (laundromat_id), INDEX IDX_BF34A00B5AA1164F (payment_method_id), PRIMARY KEY (laundromat_id, payment_method_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_closure (id INT AUTO_INCREMENT NOT NULL, day VARCHAR(50) NOT NULL, added_date DATE NOT NULL, updated_at DATETIME NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, laundromat_id INT NOT NULL, INDEX IDX_26407E2A97F0DCEB (laundromat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_equipment (id INT AUTO_INCREMENT NOT NULL, equipment_reference NUMERIC(5, 0) DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, capacity NUMERIC(5, 0) NOT NULL, price NUMERIC(10, 2) NOT NULL, duration NUMERIC(5, 0) NOT NULL, laundromat_id INT NOT NULL, INDEX IDX_C4201497F0DCEB (laundromat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_exceptional_closure (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, reason VARCHAR(255) DEFAULT NULL, added_date DATE NOT NULL, laundromat_id INT NOT NULL, INDEX IDX_62B631697F0DCEB (laundromat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_interaction_history (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, action_reason VARCHAR(255) NOT NULL, date DATETIME NOT NULL, administrator_id INT NOT NULL, laundromat_id INT NOT NULL, INDEX IDX_8A054AB24B09E92C (administrator_id), INDEX IDX_8A054AB297F0DCEB (laundromat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_media (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, laundromat_id INT NOT NULL, media_id INT NOT NULL, INDEX IDX_46AEBDB297F0DCEB (laundromat_id), UNIQUE INDEX UNIQ_46AEBDB2EA9FDD75 (media_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_rating (id INT AUTO_INCREMENT NOT NULL, rating NUMERIC(1, 0) DEFAULT NULL, rated_at DATETIME DEFAULT NULL, comment VARCHAR(500) DEFAULT NULL, commented_at DATETIME DEFAULT NULL, response VARCHAR(500) DEFAULT NULL, responded_at DATETIME DEFAULT NULL, comment_deleted_reason VARCHAR(500) DEFAULT NULL, comment_deleted_at DATETIME DEFAULT NULL, laundromat_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F47C3AB597F0DCEB (laundromat_id), INDEX IDX_F47C3AB5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE laundromat_rating_report (date DATE NOT NULL, reason VARCHAR(50) NOT NULL, comment LONGTEXT DEFAULT NULL, rating_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_DF9658F5A32EFC6 (rating_id), INDEX IDX_DF9658F5A76ED395 (user_id), PRIMARY KEY (rating_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, location VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, size NUMERIC(10, 0) NOT NULL, mime_type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE professional (id INT AUTO_INCREMENT NOT NULL, siren NUMERIC(9, 0) NOT NULL, status VARCHAR(50) NOT NULL, validation_date DATETIME DEFAULT NULL, user_id INT NOT NULL, address_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_B3B573AAA76ED395 (user_id), UNIQUE INDEX UNIQ_B3B573AAF5B7AF75 (address_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE professional_interaction_history (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, action_reason VARCHAR(255) NOT NULL, date DATE NOT NULL, administrator_id INT NOT NULL, professional_id INT NOT NULL, INDEX IDX_2F90A1F94B09E92C (administrator_id), INDEX IDX_2F90A1F9DB77003 (professional_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, oauth_id VARCHAR(255) DEFAULT NULL, last_connection_date DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_favorite_laundromat (user_id INT NOT NULL, laundromat_id INT NOT NULL, INDEX IDX_70CB92AAA76ED395 (user_id), INDEX IDX_70CB92AA97F0DCEB (laundromat_id), PRIMARY KEY (user_id, laundromat_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_interaction_history (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, action_reason VARCHAR(255) NOT NULL, date DATETIME NOT NULL, administrator_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6D3F28584B09E92C (administrator_id), INDEX IDX_6D3F2858A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_preference (id INT AUTO_INCREMENT NOT NULL, theme VARCHAR(255) NOT NULL, notifications TINYINT NOT NULL, user_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_FA0E76BFA76ED395 (user_id), INDEX IDX_FA0E76BF82F1BAF4 (language_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE laundromat ADD CONSTRAINT FK_44C31F25DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE laundromat ADD CONSTRAINT FK_44C31F25F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE laundromat ADD CONSTRAINT FK_44C31F25F98F144A FOREIGN KEY (logo_id) REFERENCES media (id)');
        $this->addSql('ALTER TABLE laundromat_service ADD CONSTRAINT FK_8FDA692997F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_service ADD CONSTRAINT FK_8FDA6929ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE laundromat_payment_method ADD CONSTRAINT FK_BF34A00B97F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_payment_method ADD CONSTRAINT FK_BF34A00B5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)');
        $this->addSql('ALTER TABLE laundromat_closure ADD CONSTRAINT FK_26407E2A97F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_equipment ADD CONSTRAINT FK_C4201497F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure ADD CONSTRAINT FK_62B631697F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_interaction_history ADD CONSTRAINT FK_8A054AB24B09E92C FOREIGN KEY (administrator_id) REFERENCES administrator (id)');
        $this->addSql('ALTER TABLE laundromat_interaction_history ADD CONSTRAINT FK_8A054AB297F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_media ADD CONSTRAINT FK_46AEBDB297F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_media ADD CONSTRAINT FK_46AEBDB2EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id)');
        $this->addSql('ALTER TABLE laundromat_rating ADD CONSTRAINT FK_F47C3AB597F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE laundromat_rating ADD CONSTRAINT FK_F47C3AB5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT FK_DF9658F5A32EFC6 FOREIGN KEY (rating_id) REFERENCES laundromat_rating (id)');
        $this->addSql('ALTER TABLE laundromat_rating_report ADD CONSTRAINT FK_DF9658F5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE professional ADD CONSTRAINT FK_B3B573AAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE professional ADD CONSTRAINT FK_B3B573AAF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE professional_interaction_history ADD CONSTRAINT FK_2F90A1F94B09E92C FOREIGN KEY (administrator_id) REFERENCES administrator (id)');
        $this->addSql('ALTER TABLE professional_interaction_history ADD CONSTRAINT FK_2F90A1F9DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE user_favorite_laundromat ADD CONSTRAINT FK_70CB92AAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_favorite_laundromat ADD CONSTRAINT FK_70CB92AA97F0DCEB FOREIGN KEY (laundromat_id) REFERENCES laundromat (id)');
        $this->addSql('ALTER TABLE user_interaction_history ADD CONSTRAINT FK_6D3F28584B09E92C FOREIGN KEY (administrator_id) REFERENCES administrator (id)');
        $this->addSql('ALTER TABLE user_interaction_history ADD CONSTRAINT FK_6D3F2858A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BF82F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE laundromat DROP FOREIGN KEY FK_44C31F25DB77003');
        $this->addSql('ALTER TABLE laundromat DROP FOREIGN KEY FK_44C31F25F5B7AF75');
        $this->addSql('ALTER TABLE laundromat DROP FOREIGN KEY FK_44C31F25F98F144A');
        $this->addSql('ALTER TABLE laundromat_service DROP FOREIGN KEY FK_8FDA692997F0DCEB');
        $this->addSql('ALTER TABLE laundromat_service DROP FOREIGN KEY FK_8FDA6929ED5CA9E6');
        $this->addSql('ALTER TABLE laundromat_payment_method DROP FOREIGN KEY FK_BF34A00B97F0DCEB');
        $this->addSql('ALTER TABLE laundromat_payment_method DROP FOREIGN KEY FK_BF34A00B5AA1164F');
        $this->addSql('ALTER TABLE laundromat_closure DROP FOREIGN KEY FK_26407E2A97F0DCEB');
        $this->addSql('ALTER TABLE laundromat_equipment DROP FOREIGN KEY FK_C4201497F0DCEB');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure DROP FOREIGN KEY FK_62B631697F0DCEB');
        $this->addSql('ALTER TABLE laundromat_interaction_history DROP FOREIGN KEY FK_8A054AB24B09E92C');
        $this->addSql('ALTER TABLE laundromat_interaction_history DROP FOREIGN KEY FK_8A054AB297F0DCEB');
        $this->addSql('ALTER TABLE laundromat_media DROP FOREIGN KEY FK_46AEBDB297F0DCEB');
        $this->addSql('ALTER TABLE laundromat_media DROP FOREIGN KEY FK_46AEBDB2EA9FDD75');
        $this->addSql('ALTER TABLE laundromat_rating DROP FOREIGN KEY FK_F47C3AB597F0DCEB');
        $this->addSql('ALTER TABLE laundromat_rating DROP FOREIGN KEY FK_F47C3AB5A76ED395');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY FK_DF9658F5A32EFC6');
        $this->addSql('ALTER TABLE laundromat_rating_report DROP FOREIGN KEY FK_DF9658F5A76ED395');
        $this->addSql('ALTER TABLE professional DROP FOREIGN KEY FK_B3B573AAA76ED395');
        $this->addSql('ALTER TABLE professional DROP FOREIGN KEY FK_B3B573AAF5B7AF75');
        $this->addSql('ALTER TABLE professional_interaction_history DROP FOREIGN KEY FK_2F90A1F94B09E92C');
        $this->addSql('ALTER TABLE professional_interaction_history DROP FOREIGN KEY FK_2F90A1F9DB77003');
        $this->addSql('ALTER TABLE user_favorite_laundromat DROP FOREIGN KEY FK_70CB92AAA76ED395');
        $this->addSql('ALTER TABLE user_favorite_laundromat DROP FOREIGN KEY FK_70CB92AA97F0DCEB');
        $this->addSql('ALTER TABLE user_interaction_history DROP FOREIGN KEY FK_6D3F28584B09E92C');
        $this->addSql('ALTER TABLE user_interaction_history DROP FOREIGN KEY FK_6D3F2858A76ED395');
        $this->addSql('ALTER TABLE user_preference DROP FOREIGN KEY FK_FA0E76BFA76ED395');
        $this->addSql('ALTER TABLE user_preference DROP FOREIGN KEY FK_FA0E76BF82F1BAF4');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE administrator');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE laundromat');
        $this->addSql('DROP TABLE laundromat_service');
        $this->addSql('DROP TABLE laundromat_payment_method');
        $this->addSql('DROP TABLE laundromat_closure');
        $this->addSql('DROP TABLE laundromat_equipment');
        $this->addSql('DROP TABLE laundromat_exceptional_closure');
        $this->addSql('DROP TABLE laundromat_interaction_history');
        $this->addSql('DROP TABLE laundromat_media');
        $this->addSql('DROP TABLE laundromat_rating');
        $this->addSql('DROP TABLE laundromat_rating_report');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE professional');
        $this->addSql('DROP TABLE professional_interaction_history');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_favorite_laundromat');
        $this->addSql('DROP TABLE user_interaction_history');
        $this->addSql('DROP TABLE user_preference');
    }
}
