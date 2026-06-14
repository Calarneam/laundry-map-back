<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add exceptional closure type and modified hours slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE laundromat_exceptional_closure ADD type VARCHAR(50) DEFAULT 'full_closure' NOT NULL");
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE start_date start_date DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\"');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE end_date end_date DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\"');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE added_date added_date DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\"');

        $this->addSql('CREATE TABLE laundromat_exceptional_closure_slot (id INT AUTO_INCREMENT NOT NULL, exceptional_closure_id INT NOT NULL, day VARCHAR(50) NOT NULL, start_time TIME NOT NULL COMMENT \"(DC2Type:time_immutable)\", end_time TIME NOT NULL COMMENT \"(DC2Type:time_immutable)\", added_date DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\", updated_at DATETIME NOT NULL COMMENT \"(DC2Type:datetime_immutable)\", INDEX IDX_C8900F319AD7D929 (exceptional_closure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure_slot ADD CONSTRAINT FK_C8900F319AD7D929 FOREIGN KEY (exceptional_closure_id) REFERENCES laundromat_exceptional_closure (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE laundromat_exceptional_closure_slot DROP FOREIGN KEY FK_C8900F319AD7D929');
        $this->addSql('DROP TABLE laundromat_exceptional_closure_slot');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure DROP type');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE start_date start_date DATE NOT NULL COMMENT \"(DC2Type:date_immutable)\"');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE end_date end_date DATE NOT NULL COMMENT \"(DC2Type:date_immutable)\"');
        $this->addSql('ALTER TABLE laundromat_exceptional_closure CHANGE added_date added_date DATE NOT NULL COMMENT \"(DC2Type:date_immutable)\"');
    }
}
