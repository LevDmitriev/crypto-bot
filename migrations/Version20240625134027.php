<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240625134027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE coin_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE coin (id INT NOT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, coin_id INT NOT NULL, by_bit_id VARCHAR(255) NOT NULL, quantity VARCHAR(255) NOT NULL, price VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F529939884BBDA7 ON "order" (coin_id)');
        $this->addSql('CREATE INDEX bybit_id ON "order" (by_bit_id)');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939884BBDA7 FOREIGN KEY (coin_id) REFERENCES coin (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE coin_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "order_id_seq" CASCADE');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939884BBDA7');
        $this->addSql('DROP TABLE coin');
        $this->addSql('DROP TABLE "order"');
    }
}
