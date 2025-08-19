<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819162341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, asin VARCHAR(20) NOT NULL, title VARCHAR(500) NOT NULL, slug VARCHAR(255) NOT NULL, brand VARCHAR(255) NOT NULL, manufacturer VARCHAR(255) DEFAULT NULL, amazon_url LONGTEXT NOT NULL, features JSON NOT NULL, source_data JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_D34A04ADEA5C05C2 (asin), UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_image (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, url VARCHAR(500) NOT NULL, width INT NOT NULL, height INT NOT NULL, is_primary TINYINT(1) NOT NULL, type VARCHAR(50) NOT NULL, order_position INT DEFAULT NULL, alt_text VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_64617F034584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_price (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, listing_id VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, display_amount VARCHAR(50) NOT NULL, savings_amount NUMERIC(10, 2) DEFAULT NULL, savings_display VARCHAR(50) DEFAULT NULL, savings_percentage INT DEFAULT NULL, is_free_shipping TINYINT(1) NOT NULL, violates_map TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_6B9459854584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_ranking (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, category_id VARCHAR(50) NOT NULL, category_name VARCHAR(255) NOT NULL, context_free_name VARCHAR(255) DEFAULT NULL, sales_rank INT NOT NULL, is_root TINYINT(1) NOT NULL, ranking_date DATE NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_516122604584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_price ADD CONSTRAINT FK_6B9459854584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_ranking ADD CONSTRAINT FK_516122604584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('ALTER TABLE product_price DROP FOREIGN KEY FK_6B9459854584665A');
        $this->addSql('ALTER TABLE product_ranking DROP FOREIGN KEY FK_516122604584665A');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('DROP TABLE product_price');
        $this->addSql('DROP TABLE product_ranking');
    }
}
