<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819143708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_diffusion DROP FOREIGN KEY FK_5633DE70A76ED395');
        $this->addSql('ALTER TABLE email_diffusion DROP FOREIGN KEY FK_5633DE70F639F774');
        $this->addSql('ALTER TABLE email_error DROP FOREIGN KEY FK_C090FDFEC7C8C2E2');
        $this->addSql('ALTER TABLE email_campaign DROP FOREIGN KEY FK_14730D94D395B25E');
        $this->addSql('ALTER TABLE email_tracking DROP FOREIGN KEY FK_A31A7D55C7C8C2E2');
        $this->addSql('DROP TABLE email_diffusion');
        $this->addSql('DROP TABLE email_error');
        $this->addSql('DROP TABLE email_campaign');
        $this->addSql('DROP TABLE email_tracking');
        $this->addSql('DROP TABLE email_filter');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_diffusion (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, user_id INT DEFAULT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5633DE70F639F774 (campaign_id), INDEX IDX_5633DE70A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE email_error (id INT AUTO_INCREMENT NOT NULL, diffusion_id INT DEFAULT NULL, code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, fulldata JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C090FDFEC7C8C2E2 (diffusion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE email_campaign (id INT AUTO_INCREMENT NOT NULL, filter_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, subject VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, template VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, require_sendgrid TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_14730D94D395B25E (filter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE email_tracking (id INT AUTO_INCREMENT NOT NULL, diffusion_id INT DEFAULT NULL, event VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, url VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, navigator VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A31A7D55C7C8C2E2 (diffusion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE email_filter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, method VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_count INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE email_diffusion ADD CONSTRAINT FK_5633DE70A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE email_diffusion ADD CONSTRAINT FK_5633DE70F639F774 FOREIGN KEY (campaign_id) REFERENCES email_campaign (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE email_error ADD CONSTRAINT FK_C090FDFEC7C8C2E2 FOREIGN KEY (diffusion_id) REFERENCES email_diffusion (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE email_campaign ADD CONSTRAINT FK_14730D94D395B25E FOREIGN KEY (filter_id) REFERENCES email_filter (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE email_tracking ADD CONSTRAINT FK_A31A7D55C7C8C2E2 FOREIGN KEY (diffusion_id) REFERENCES email_diffusion (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
