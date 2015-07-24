<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/07/24 10:29:30
 */
class Version20150724102926 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__credit_support (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                credit_amount INT NOT NULL, 
                credit_used INT NOT NULL, 
                INDEX IDX_A5B3ACEE7E3C61F9 (owner_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE formalibre__credit_support 
            ADD CONSTRAINT FK_A5B3ACEE7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE formalibre__credit_support
        ");
    }
}