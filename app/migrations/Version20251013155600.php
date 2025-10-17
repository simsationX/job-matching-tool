<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013155600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_area (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D450E6FA5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candidate_activity_area (candidate_id INT NOT NULL, activity_area_id INT NOT NULL, INDEX IDX_FA26FDD791BD8781 (candidate_id), INDEX IDX_FA26FDD7BD5D367C (activity_area_id), PRIMARY KEY(candidate_id, activity_area_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candidate_activity_area ADD CONSTRAINT FK_FA26FDD791BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_activity_area ADD CONSTRAINT FK_FA26FDD7BD5D367C FOREIGN KEY (activity_area_id) REFERENCES activity_area (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate ADD additional_activity_areas VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_activity_area DROP FOREIGN KEY FK_FA26FDD791BD8781');
        $this->addSql('ALTER TABLE candidate_activity_area DROP FOREIGN KEY FK_FA26FDD7BD5D367C');
        $this->addSql('DROP TABLE activity_area');
        $this->addSql('DROP TABLE candidate_activity_area');
        $this->addSql('ALTER TABLE candidate DROP additional_activity_areas');
    }
}
