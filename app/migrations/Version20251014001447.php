<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014001447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_geo_city (job_id INT NOT NULL, geo_city_id INT NOT NULL, INDEX IDX_4E3B884FBE04EA9 (job_id), INDEX IDX_4E3B884F3CBAEFAD (geo_city_id), PRIMARY KEY(job_id, geo_city_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_geo_city ADD CONSTRAINT FK_4E3B884FBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_geo_city ADD CONSTRAINT FK_4E3B884F3CBAEFAD FOREIGN KEY (geo_city_id) REFERENCES geo_city (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_geo_city DROP FOREIGN KEY FK_4E3B884FBE04EA9');
        $this->addSql('ALTER TABLE job_geo_city DROP FOREIGN KEY FK_4E3B884F3CBAEFAD');
        $this->addSql('DROP TABLE job_geo_city');
    }
}
