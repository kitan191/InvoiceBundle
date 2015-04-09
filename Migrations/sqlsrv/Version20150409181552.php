<?php

namespace FormaLibre\InvoiceBundle\Migrations\sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/09 06:15:53
 */
class Version20150409181552 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__product (
                id INT IDENTITY NOT NULL, 
                code NVARCHAR(255) NOT NULL, 
                type NVARCHAR(255) NOT NULL, 
                details VARCHAR(MAX), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_53C6972477153098 ON formalibre__product (code) 
            WHERE code IS NOT NULL
        ");
        $this->addSql("
            CREATE TABLE formalibre__order (
                id INT IDENTITY NOT NULL, 
                product_id INT, 
                price_solution_id INT, 
                owner_id INT, 
                vatAmount DOUBLE PRECISION, 
                vatRate DOUBLE PRECISION, 
                ipAddress NVARCHAR(255), 
                countryCode NVARCHAR(255), 
                vatNumber NVARCHAR(255), 
                amount DOUBLE PRECISION, 
                extendedData VARCHAR(MAX), 
                creation_date DATETIME2(6) NOT NULL, 
                validation_date DATETIME2(6), 
                paymentInstruction_id INT, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_62CE339EFD913E4D ON formalibre__order (paymentInstruction_id) 
            WHERE paymentInstruction_id IS NOT NULL
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E4584665A ON formalibre__order (product_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E1BD2AD95 ON formalibre__order (price_solution_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E7E3C61F9 ON formalibre__order (owner_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre__price_solution (
                id INT IDENTITY NOT NULL, 
                product_id INT, 
                monthDuration INT NOT NULL, 
                price DOUBLE PRECISION NOT NULL, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_E2B632A84584665A ON formalibre__price_solution (product_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre__shared_workspace (
                id INT IDENTITY NOT NULL, 
                owner_id INT NOT NULL, 
                product_id INT, 
                remoteId INT NOT NULL, 
                end_date DATETIME2(6), 
                maxSize NVARCHAR(255) NOT NULL, 
                maxUser INT NOT NULL, 
                maxRes INT NOT NULL, 
                autoSubscribe BIT NOT NULL, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C27E3C61F9 ON formalibre__shared_workspace (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C24584665A ON formalibre__shared_workspace (product_id)
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
            DROP CONSTRAINT FK_62CE339E4584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution 
            DROP CONSTRAINT FK_E2B632A84584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__shared_workspace 
            DROP CONSTRAINT FK_1559C4C24584665A
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP CONSTRAINT FK_62CE339E1BD2AD95
        ");
        $this->addSql("
            DROP TABLE formalibre__product
        ");
        $this->addSql("
            DROP TABLE formalibre__order
        ");
        $this->addSql("
            DROP TABLE formalibre__price_solution
        ");
        $this->addSql("
            DROP TABLE formalibre__shared_workspace
        ");
    }
}