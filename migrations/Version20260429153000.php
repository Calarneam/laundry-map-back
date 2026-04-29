<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store address coordinates as GeoJSON Point in JSON column `position`, drop latitude/longitude';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD position JSON DEFAULT NULL');
        $this->addSql("UPDATE address SET position = JSON_OBJECT('type', 'Point', 'coordinates', JSON_ARRAY(longitude, latitude)) WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $this->addSql('ALTER TABLE address DROP latitude, DROP longitude');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD latitude NUMERIC(10, 7) DEFAULT NULL, ADD longitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql("UPDATE address SET latitude = CAST(JSON_UNQUOTE(JSON_EXTRACT(position, '$.coordinates[1]')) AS DECIMAL(10,7)), longitude = CAST(JSON_UNQUOTE(JSON_EXTRACT(position, '$.coordinates[0]')) AS DECIMAL(10,7)) WHERE position IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(position, '$.type')) = 'Point'");
        $this->addSql('ALTER TABLE address DROP position');
    }
}
