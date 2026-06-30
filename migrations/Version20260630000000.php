<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add social links to laundromats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE social_link_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, validation_regex LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_SOCIAL_LINK_TYPE_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE laundromat_social_link (id INT AUTO_INCREMENT NOT NULL, laundromat_id INT NOT NULL, type_id INT NOT NULL, url VARCHAR(2048) NOT NULL, INDEX IDX_LAUNDROMAT_SOCIAL_LINK_LAUNDROMAT (laundromat_id), INDEX IDX_LAUNDROMAT_SOCIAL_LINK_TYPE (type_id), UNIQUE INDEX UNIQ_LAUNDROMAT_SOCIAL_LINK_TYPE (laundromat_id, type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE laundromat_social_link ADD CONSTRAINT FK_LAUNDROMAT_SOCIAL_LINK_LAUNDROMAT FOREIGN KEY (laundromat_id) REFERENCES laundromat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE laundromat_social_link ADD CONSTRAINT FK_LAUNDROMAT_SOCIAL_LINK_TYPE FOREIGN KEY (type_id) REFERENCES social_link_type (id)');

        $this->addSql("INSERT INTO social_link_type (code, label, validation_regex) VALUES ('website', 'Site Web', '/^https?:\\\\/\\\\/(?:[a-z0-9-]+\\\\.)+[a-z]{2,}(?:[\\\\/?#][^\\\\s]*)?$/i')");
        $this->addSql("INSERT INTO social_link_type (code, label, validation_regex) VALUES ('facebook', 'Page Facebook', '/^https?:\\\\/\\\\/(?:www\\\\.|m\\\\.)?facebook\\\\.com\\\\/(?:profile\\\\.php\\\\?id=\\\\d+|(?:pages\\\\/)?[A-Za-z0-9._-]+(?:\\\\/\\\\d+)?)\\\\/?(?:[?#][^\\\\s]*)?$/i')");
        $this->addSql("INSERT INTO social_link_type (code, label, validation_regex) VALUES ('instagram', 'Compte Instagram', '/^https?:\\\\/\\\\/(?:www\\\\.)?instagram\\\\.com\\\\/[A-Za-z0-9._]{1,30}\\\\/?(?:[?#][^\\\\s]*)?$/i')");
        $this->addSql("INSERT INTO social_link_type (code, label, validation_regex) VALUES ('x', 'Compte X', '/^https?:\\\\/\\\\/(?:www\\\\.)?(?:x\\\\.com|twitter\\\\.com)\\\\/[A-Za-z0-9_]{1,15}\\\\/?(?:[?#][^\\\\s]*)?$/i')");
        $this->addSql("INSERT INTO social_link_type (code, label, validation_regex) VALUES ('linkedin', 'Page LinkedIn', '/^https?:\\\\/\\\\/(?:www\\\\.)?linkedin\\\\.com\\\\/(?:company|showcase)\\\\/[A-Za-z0-9_.-]+\\\\/?(?:[?#][^\\\\s]*)?$/i')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat_social_link DROP FOREIGN KEY FK_LAUNDROMAT_SOCIAL_LINK_LAUNDROMAT');
        $this->addSql('ALTER TABLE laundromat_social_link DROP FOREIGN KEY FK_LAUNDROMAT_SOCIAL_LINK_TYPE');
        $this->addSql('DROP TABLE laundromat_social_link');
        $this->addSql('DROP TABLE social_link_type');
    }
}
