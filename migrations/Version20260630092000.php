<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional social links (website, facebook, instagram, x, linkedin) to laundromat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat ADD website_url VARCHAR(255) DEFAULT NULL, ADD facebook_url VARCHAR(255) DEFAULT NULL, ADD instagram_url VARCHAR(255) DEFAULT NULL, ADD x_url VARCHAR(255) DEFAULT NULL, ADD linkedin_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat DROP website_url, DROP facebook_url, DROP instagram_url, DROP x_url, DROP linkedin_url');
    }
}
