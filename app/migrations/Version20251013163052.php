<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013163052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidate_additional_industry (candidate_id INT NOT NULL, industry_id INT NOT NULL, INDEX IDX_A7AEF4A791BD8781 (candidate_id), INDEX IDX_A7AEF4A72B19A734 (industry_id), PRIMARY KEY(candidate_id, industry_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE industry (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_CDFA6CA05E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candidate_additional_industry ADD CONSTRAINT FK_A7AEF4A791BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_additional_industry ADD CONSTRAINT FK_A7AEF4A72B19A734 FOREIGN KEY (industry_id) REFERENCES industry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate CHANGE branche industry VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_additional_industry DROP FOREIGN KEY FK_A7AEF4A791BD8781');
        $this->addSql('ALTER TABLE candidate_additional_industry DROP FOREIGN KEY FK_A7AEF4A72B19A734');
        $this->addSql('DROP TABLE candidate_additional_industry');
        $this->addSql('DROP TABLE industry');
        $this->addSql('ALTER TABLE candidate CHANGE industry branche VARCHAR(255) DEFAULT NULL');
    }
}
