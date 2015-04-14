<?php

namespace FormaLibre\InvoiceBundle\Migrations\drizzle_pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/14 01:44:03
 */
class Version20150414134402 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__product (
                id INT AUTO_INCREMENT NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                details TEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', 
                PRIMARY KEY(id), 
                UNIQUE INDEX UNIQ_53C6972477153098 (code)
            )
        ");
        $this->addSql("
            CREATE TABLE formalibre__order (
                id INT AUTO_INCREMENT NOT NULL, 
                product_id INT DEFAULT NULL, 
                price_solution_id INT DEFAULT NULL, 
                owner_id INT DEFAULT NULL, 
                vatAmount DOUBLE PRECISION DEFAULT NULL, 
                vatRate DOUBLE PRECISION DEFAULT NULL, 
                ipAddress VARCHAR(255) DEFAULT NULL, 
                countryCode VARCHAR(255) DEFAULT NULL, 
                vatNumber VARCHAR(255) DEFAULT NULL, 
                amount DOUBLE PRECISION DEFAULT NULL, 
                extendedData TEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', 
                creation_date DATETIME NOT NULL, 
                validation_date DATETIME DEFAULT NULL, 
                paymentInstruction_id INT DEFAULT NULL, 
                PRIMARY KEY(id), 
                UNIQUE INDEX UNIQ_62CE339EFD913E4D (paymentInstruction_id), 
                INDEX IDX_62CE339E4584665A (product_id), 
                INDEX IDX_62CE339E1BD2AD95 (price_solution_id), 
                INDEX IDX_62CE339E7E3C61F9 (owner_id)
            )
        ");
        $this->addSql("
            CREATE TABLE formalibre__free_test_month_usage (
                id INT AUTO_INCREMENT NOT NULL, 
                user_id INT DEFAULT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_110D0DB6A76ED395 (user_id)
            )
        ");
        $this->addSql("
            CREATE TABLE formalibre__price_solution (
                id INT AUTO_INCREMENT NOT NULL, 
                product_id INT DEFAULT NULL, 
                monthDuration INT NOT NULL, 
                price DOUBLE PRECISION NOT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_E2B632A84584665A (product_id)
            )
        ");
        $this->addSql("
            CREATE TABLE formalibre__shared_workspace (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                product_id INT DEFAULT NULL, 
                remoteId INT NOT NULL, 
                end_date DATETIME DEFAULT NULL, 
                maxSize VARCHAR(255) NOT NULL, 
                maxUser INT NOT NULL, 
                maxRes INT NOT NULL, 
                autoSubscribe BOOLEAN NOT NULL, 
                isTest INT NOT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_1559C4C27E3C61F9 (owner_id), 
                INDEX IDX_1559C4C24584665A (product_id)
            )
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339EFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E4584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E1BD2AD95 FOREIGN KEY (price_solution_id) 
            REFERENCES formalibre__price_solution (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__free_test_month_usage 
            ADD CONSTRAINT FK_110D0DB6A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution 
            ADD CONSTRAINT FK_E2B632A84584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            ADD CONSTRAINT FK_1559C4C27E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            ADD CONSTRAINT FK_1559C4C24584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre__product (id) 
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP FOREIGN KEY FK_62CE339E4584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution 
            DROP FOREIGN KEY FK_E2B632A84584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            DROP FOREIGN KEY FK_1559C4C24584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP FOREIGN KEY FK_62CE339E1BD2AD95
        ");
        $this->addSql("
            DROP TABLE formalibre__product
        ");
        $this->addSql("
            DROP TABLE formalibre__order
        ");
        $this->addSql("
            DROP TABLE formalibre__free_test_month_usage
        ");
        $this->addSql("
            DROP TABLE formalibre__price_solution
        ");
        $this->addSql("
            DROP TABLE formalibre__shared_workspace
        ");
    }
}