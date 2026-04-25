<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account table for fund transfer API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account (
            id INT AUTO_INCREMENT NOT NULL,
            currency VARCHAR(3) NOT NULL,
            balance NUMERIC(15, 2) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account');
    }
}
