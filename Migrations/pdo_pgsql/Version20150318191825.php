<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_pgsql;

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
                id SERIAL NOT NULL, 
                paymentInstruction_id INT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F259060AFD913E4D ON formalibre_order_workspace (paymentInstruction_id)
        ");
        $this->addSql("
            CREATE TABLE formalibre_product (
                id SERIAL NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                details TEXT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_C38DA1BB77153098 ON formalibre_product (code)
        ");
        $this->addSql("
            COMMENT ON COLUMN formalibre_product.details IS '(DC2Type:json_array)'
        ");
        $this->addSql("
            CREATE TABLE formalibre_workspace_product (
                id SERIAL NOT NULL, 
                owner_id INT NOT NULL, 
                product_id INT DEFAULT NULL, 
                code VARCHAR(256) NOT NULL, 
                name VARCHAR(256) NOT NULL, 
                end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                maxSize VARCHAR(255) NOT NULL, 
                maxUser INT NOT NULL, 
                maxRes INT NOT NULL, 
                PRIMARY KEY(id)
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
            REFERENCES payment_instructions (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ");
        $this->addSql("
            ALTER TABLE formalibre_workspace_product 
            ADD CONSTRAINT FK_98490B4A4584665A FOREIGN KEY (product_id) 
            REFERENCES formalibre_product (id) 
            ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
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