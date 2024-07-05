<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240704123725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE coins_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE positions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE coins (id INT NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE orders (id UUID NOT NULL, coin_id INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION DEFAULT NULL, type VARCHAR(255) DEFAULT \'Market\' NOT NULL, side VARCHAR(255) NOT NULL, category VARCHAR(255) DEFAULT \'spot\' NOT NULL, by_bit_status VARCHAR(255) DEFAULT \'New\' NOT NULL, status VARCHAR(255) DEFAULT NULL, average_price DOUBLE PRECISION DEFAULT NULL, cumulative_executed_quantity DOUBLE PRECISION DEFAULT NULL, cumulative_executed_value DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E52FFDEE84BBDA7 ON orders (coin_id)');
        $this->addSql('COMMENT ON COLUMN orders.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE positions (id INT NOT NULL, buy_order_id UUID DEFAULT NULL, sell_order_id UUID DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D69FE57C7FC358ED ON positions (buy_order_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D69FE57C6CF89127 ON positions (sell_order_id)');
        $this->addSql('COMMENT ON COLUMN positions.buy_order_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN positions.sell_order_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE84BBDA7 FOREIGN KEY (coin_id) REFERENCES coins (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE positions ADD CONSTRAINT FK_D69FE57C7FC358ED FOREIGN KEY (buy_order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE positions ADD CONSTRAINT FK_D69FE57C6CF89127 FOREIGN KEY (sell_order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE coins_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE positions_id_seq CASCADE');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE84BBDA7');
        $this->addSql('ALTER TABLE positions DROP CONSTRAINT FK_D69FE57C7FC358ED');
        $this->addSql('ALTER TABLE positions DROP CONSTRAINT FK_D69FE57C6CF89127');
        $this->addSql('DROP TABLE coins');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE positions');
    }
}
