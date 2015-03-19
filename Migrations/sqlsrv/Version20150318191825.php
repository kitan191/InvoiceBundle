<?php

namespace FormaLibre\InvoiceBundle\Migrations\sqlsrv;

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
                id INT IDENTITY NOT NULL, 
                paymentInstruction_id INT, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F259060AFD913E4D ON formalibre_order_workspace (paymentInstruction_id) 
            WHERE paymentInstruction_id IS NOT NULL
        ");
        $this->addSql("
            CREATE TABLE formalibre_product (
                id INT IDENTITY NOT NULL, 
                code NVARCHAR(255) NOT NULL, 
                type NVARCHAR(255) NOT NULL, 
                details VARCHAR(MAX), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_C38DA1BB77153098 ON formalibre_product (code) 
            WHERE code IS NOT NULL
        ");
        $this->addSql("
            CREATE TABLE formalibre_workspace_product (
                id INT IDENTITY NOT NULL, 
                owner_id INT NOT NULL, 
                product_id INT, 
                code NVARCHAR(256) NOT NULL, 
                name NVARCHAR(256) NOT NULL, 
                end_date DATETIME2(6), 
                maxSize NVARCHAR(255) NOT NULL, 
                maxUser INT NOT NULL, 
                maxRes INT NOT NULL, 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A7E3C61F9 ON formalibre_workspace_product (owner_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_98490B4A4584665A ON formalibre_workspace_product (product_id)
        ");
        $this->addSql("
            ALTER TABLE formalibre_order_workspace 
            ADD CONSTRAINT FK_F259060AFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A4584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre_product (id) 
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            DROP CONSTRAINT FK_98490B4A4584665A
        ");
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