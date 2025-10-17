<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007123025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidate (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, position VARCHAR(255) DEFAULT NULL, branche VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, additional_locations VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candidate_job_match (id INT AUTO_INCREMENT NOT NULL, candidate_id INT NOT NULL, company VARCHAR(255) NOT NULL, company_phone VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, contact_person VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(255) DEFAULT NULL, position VARCHAR(255) NOT NULL, position_id BIGINT DEFAULT NULL, ad_id BIGINT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, found_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_647CA55991BD8781 (candidate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, company VARCHAR(255) NOT NULL, company_phone VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, contact_person VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(255) DEFAULT NULL, position VARCHAR(255) NOT NULL, position_id BIGINT DEFAULT NULL, ad_id BIGINT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candidate_job_match ADD CONSTRAINT FK_647CA55991BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_job_match DROP FOREIGN KEY FK_647CA55991BD8781');
        $this->addSql('DROP TABLE candidate');
        $this->addSql('DROP TABLE candidate_job_match');
        $this->addSql('DROP TABLE job');
    }
}
