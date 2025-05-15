<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515111826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984D4CFF2B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F52993984D4CFF2B ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD shipping_line1 VARCHAR(255) NOT NULL, ADD shipping_line2 VARCHAR(255) NOT NULL, ADD shipping_city VARCHAR(30) NOT NULL, ADD shipping_country VARCHAR(30) NOT NULL, ADD shipping_zip_code VARCHAR(30) NOT NULL, DROP shipping_address_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD shipping_address_id INT NOT NULL, DROP shipping_line1, DROP shipping_line2, DROP shipping_city, DROP shipping_country, DROP shipping_zip_code
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F52993984D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES address (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F52993984D4CFF2B ON `order` (shipping_address_id)
        SQL);
    }
}
