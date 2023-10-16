<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231004224229 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `loan` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `amount` INT UNSIGNED NOT NULL COMMENT 'in cents',
                `term` SMALLINT UNSIGNED NOT NULL COMMENT 'in months',
                `interest_rate` SMALLINT UNSIGNED NOT NULL COMMENT 'in basis points',
                `default_euribor_rate` SMALLINT UNSIGNED NOT NULL COMMENT 'in basis points',
                PRIMARY KEY (`id`) USING BTREE
            ) ENGINE=InnoDB;
        ");

        $this->addSql("
            CREATE TABLE `euribor` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `loan_id` INT UNSIGNED NOT NULL,
                `segment_number` SMALLINT UNSIGNED NOT NULL,
                `rate` SMALLINT UNSIGNED NOT NULL COMMENT 'in basis points',
                PRIMARY KEY (`id`) USING BTREE,
                UNIQUE INDEX `loan_id_segment_number` (`loan_id`, `segment_number`) USING BTREE,
                INDEX `load_id` (`loan_id`) USING BTREE,
                CONSTRAINT `FK_euribor__loan_id__loan__loan_id` FOREIGN KEY (`loan_id`) 
                    REFERENCES `loan` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `euribor`;');
        $this->addSql('DROP TABLE `loan`;');
    }
}
