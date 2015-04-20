<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/20 12:40:59
 */
class Version20150420124058 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD COLUMN hasDiscout BOOLEAN NOT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX UNIQ_62CE339EFD913E4D
        ");
        $this->addSql("
            DROP INDEX IDX_62CE339E4584665A
        ");
        $this->addSql("
            DROP INDEX IDX_62CE339E1BD2AD95
        ");
        $this->addSql("
            DROP INDEX IDX_62CE339E7E3C61F9
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__formalibre__order AS 
            SELECT id, 
            product_id, 
            price_solution_id, 
            owner_id, 
            vatAmount, 
            vatRate, 
            ipAddress, 
            countryCode, 
            vatNumber, 
            amount, 
            extendedData, 
            creation_date, 
            validation_date, 
            paymentInstruction_id 
            FROM formalibre__order
        ");
        $this->addSql("
            DROP TABLE formalibre__order
        ");
        $this->addSql("
            CREATE TABLE formalibre__order (
                id INTEGER NOT NULL, 
                product_id INTEGER DEFAULT NULL, 
                price_solution_id INTEGER DEFAULT NULL, 
                owner_id INTEGER DEFAULT NULL, 
                vatAmount DOUBLE PRECISION DEFAULT NULL, 
                vatRate DOUBLE PRECISION DEFAULT NULL, 
                ipAddress VARCHAR(255) DEFAULT NULL, 
                countryCode VARCHAR(255) DEFAULT NULL, 
                vatNumber VARCHAR(255) DEFAULT NULL, 
                amount DOUBLE PRECISION DEFAULT NULL, 
                extendedData CLOB DEFAULT NULL, 
                creation_date DATETIME NOT NULL, 
                validation_date DATETIME DEFAULT NULL, 
                paymentInstruction_id INTEGER DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_62CE339EFD913E4D FOREIGN KEY (paymentInstruction_id) 
                REFERENCES payment_instructions (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_62CE339E4584665A FOREIGN KEY (product_id) 
                REFERENCES formalibre__product (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_62CE339E1BD2AD95 FOREIGN KEY (price_solution_id) 
                REFERENCES formalibre__price_solution (id) 
                ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_62CE339E7E3C61F9 FOREIGN KEY (owner_id) 
                REFERENCES claro_user (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO formalibre__order (
                id, product_id, price_solution_id, 
                owner_id, vatAmount, vatRate, ipAddress, 
                countryCode, vatNumber, amount, extendedData, 
                creation_date, validation_date, 
                paymentInstruction_id
            ) 
            SELECT id, 
            product_id, 
            price_solution_id, 
            owner_id, 
            vatAmount, 
            vatRate, 
            ipAddress, 
            countryCode, 
            vatNumber, 
            amount, 
            extendedData, 
            creation_date, 
            validation_date, 
            paymentInstruction_id 
            FROM __temp__formalibre__order
        ");
        $this->addSql("
            DROP TABLE __temp__formalibre__order
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_62CE339EFD913E4D ON formalibre__order (paymentInstruction_id)
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
    }
}