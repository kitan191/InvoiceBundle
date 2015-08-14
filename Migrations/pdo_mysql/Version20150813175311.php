<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/08/13 05:53:11
 */
class Version20150813175311 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__partner (
                id INT AUTO_INCREMENT NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                code VARCHAR(255) NOT NULL, 
                isActivated TINYINT(1) NOT NULL, 
                UNIQUE INDEX UNIQ_B1A7AD9F5E237E06 (name), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE partner_user (
                partner_id INT NOT NULL, 
                user_id INT NOT NULL, 
                INDEX IDX_DDA7E5519393F8FE (partner_id), 
                INDEX IDX_DDA7E551A76ED395 (user_id), 
                PRIMARY KEY(partner_id, user_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE partner_user 
            ADD CONSTRAINT FK_DDA7E5519393F8FE FOREIGN KEY (partner_id) 
            REFERENCES formalibre__partner (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE partner_user 
            ADD CONSTRAINT FK_DDA7E551A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE partner_user 
            DROP FOREIGN KEY FK_DDA7E5519393F8FE
        ");
        $this->addSql("
            DROP TABLE formalibre__partner
        ");
        $this->addSql("
            DROP TABLE partner_user
        ");
    }
}