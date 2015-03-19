<?php

namespace FormaLibre\InvoiceBundle\Migrations\mysqli;

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
                id INT AUTO_INCREMENT NOT NULL, 
                paymentInstruction_id INT DEFAULT NULL, 
                UNIQUE INDEX UNIQ_F259060AFD913E4D (paymentInstruction_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE formalibre_product (
                id INT AUTO_INCREMENT NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', 
                UNIQUE INDEX UNIQ_C38DA1BB77153098 (code), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE formalibre_workspace_product (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                product_id INT DEFAULT NULL, 
                code VARCHAR(256) NOT NULL, 
                name VARCHAR(256) NOT NULL, 
                end_date DATETIME DEFAULT NULL, 
                maxSize VARCHAR(255) NOT NULL, 
                maxUser INT NOT NULL, 
                maxRes INT NOT NULL, 
                INDEX IDX_98490B4A7E3C61F9 (owner_id), 
                INDEX IDX_98490B4A4584665A (product_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
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
            DROP FOREIGN KEY FK_98490B4A4584665A
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