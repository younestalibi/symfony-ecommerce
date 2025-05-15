<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515090913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD shipping_address_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F52993984D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES address (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F52993984D4CFF2B ON `order` (shipping_address_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984D4CFF2B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F52993984D4CFF2B ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP shipping_address_id
        SQL);
    }
}
