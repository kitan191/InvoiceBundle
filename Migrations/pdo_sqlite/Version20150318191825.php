<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/18 07:18:26
 */
class Version20150318191825 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre_order_workspace (
                id INTEGER NOT NULL, 
                paymentInstruction_id INTEGER DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F259060AFD913E4D ON formalibre_order_workspace (paymentInstruction_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre_product (
                id INTEGER NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                details CLOB DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_C38DA1BB77153098 ON formalibre_product (code)
        ");
        $this->addSql("
            CREATE TABLE formalibre_workspace_product (
                id INTEGER NOT NULL, 
                owner_id INTEGER NOT NULL, 
                product_id INTEGER DEFAULT NULL, 
                code VARCHAR(256) NOT NULL, 
                name VARCHAR(256) NOT NULL, 
                end_date DATETIME DEFAULT NULL, 
                maxSize VARCHAR(255) NOT NULL, 
                maxUser INTEGER NOT NULL, 
                maxRes INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A7E3C61F9 ON formalibre_workspace_product (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A4584665A ON formalibre_workspace_product (product_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE formalibre_order_workspace
        ");
        $this->addSql("
            DROP TABLE formalibre_product
        ");
        $this->addSql("
            DROP TABLE formalibre_workspace_product
        ");
    }
}