<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/19 11:25:17
 */
class Version20150319112516 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__product (
                id INTEGER NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                details CLOB DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_53C6972477153098 ON formalibre__product (code)
        ");
        $this->addSql("
            CREATE TABLE formalibre__order (
                id INTEGER NOT NULL, 
                product_id INTEGER DEFAULT NULL, 
                price_solution_id INTEGER DEFAULT NULL, 
                paymentInstruction_id INTEGER DEFAULT NULL, 
                PRIMARY KEY(id)
            )
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
            CREATE TABLE formalibre__price_solution (
                id INTEGER NOT NULL, 
                product_id INTEGER DEFAULT NULL, 
                monthDuration INTEGER NOT NULL, 
                price DOUBLE PRECISION NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_E2B632A84584665A ON formalibre__price_solution (product_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre__shared_workspace (
                id INTEGER NOT NULL, 
                owner_id INTEGER NOT NULL, 
                product_id INTEGER DEFAULT NULL, 
                code VARCHAR(256) NOT NULL, 
                name VARCHAR(256) NOT NULL, 
                end_date DATETIME DEFAULT NULL, 
                maxSize VARCHAR(255) NOT NULL, 
                maxUser INTEGER NOT NULL, 
                maxRes INTEGER NOT NULL, 
                autoSubscribe BOOLEAN NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C27E3C61F9 ON formalibre__shared_workspace (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_1559C4C24584665A ON formalibre__shared_workspace (product_id)
        ");
    }

    public function down(Schema $schema)
    {
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