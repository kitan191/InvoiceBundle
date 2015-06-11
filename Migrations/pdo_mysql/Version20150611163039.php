<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/06/11 04:30:40
 */
class Version20150611163039 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE formalibre__chart (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT DEFAULT NULL, 
                creation_date DATETIME NOT NULL, 
                validation_date DATETIME DEFAULT NULL, 
                ipAddress VARCHAR(255) DEFAULT NULL, 
                paymentInstruction_id INT DEFAULT NULL, 
                UNIQUE INDEX UNIQ_72B18A2CFD913E4D (paymentInstruction_id), 
                INDEX IDX_72B18A2C7E3C61F9 (owner_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE formalibre__invoice (
                id INT AUTO_INCREMENT NOT NULL, 
                chart_id INT DEFAULT NULL, 
                isPayed TINYINT(1) NOT NULL, 
                vatAmount DOUBLE PRECISION DEFAULT NULL, 
                vatRate DOUBLE PRECISION DEFAULT NULL, 
                vatNumber VARCHAR(255) DEFAULT NULL, 
                amount DOUBLE PRECISION DEFAULT NULL, 
                total DOUBLE PRECISION DEFAULT NULL, 
                countryCode VARCHAR(255) DEFAULT NULL, 
                UNIQUE INDEX UNIQ_10E984CDBEF83E0A (chart_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE formalibre__chart 
            ADD CONSTRAINT FK_72B18A2CFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre__chart 
            ADD CONSTRAINT FK_72B18A2C7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__invoice 
            ADD CONSTRAINT FK_10E984CDBEF83E0A FOREIGN KEY (chart_id) 
            REFERENCES formalibre__chart (id)
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP FOREIGN KEY FK_62CE339E7E3C61F9
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP FOREIGN KEY FK_62CE339EFD913E4D
        ");
        $this->addSql("
            DROP INDEX UNIQ_62CE339EFD913E4D ON formalibre__order
        ");
        $this->addSql("
            DROP INDEX IDX_62CE339E7E3C61F9 ON formalibre__order
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD chart_id INT DEFAULT NULL, 
            DROP owner_id, 
            DROP ipAddress, 
            DROP countryCode, 
            DROP creation_date, 
            DROP validation_date, 
            DROP paymentInstruction_id, 
            CHANGE hasdiscout hasDiscount TINYINT(1) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339EBEF83E0A FOREIGN KEY (chart_id) 
            REFERENCES formalibre__chart (id) 
            ON DELETE SET NULL
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339EBEF83E0A ON formalibre__order (chart_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__order 
            DROP FOREIGN KEY FK_62CE339EBEF83E0A
        ");
        $this->addSql("
            ALTER TABLE formalibre__invoice 
            DROP FOREIGN KEY FK_10E984CDBEF83E0A
        ");
        $this->addSql("
            DROP TABLE formalibre__chart
        ");
        $this->addSql("
            DROP TABLE formalibre__invoice
        ");
        $this->addSql("
            DROP INDEX IDX_62CE339EBEF83E0A ON formalibre__order
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD ipAddress VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, 
            ADD countryCode VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, 
            ADD creation_date DATETIME NOT NULL, 
            ADD validation_date DATETIME DEFAULT NULL, 
            ADD paymentInstruction_id INT DEFAULT NULL, 
            CHANGE chart_id owner_id INT DEFAULT NULL, 
            CHANGE hasdiscount hasDiscout TINYINT(1) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339E7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE formalibre__order 
            ADD CONSTRAINT FK_62CE339EFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_62CE339EFD913E4D ON formalibre__order (paymentInstruction_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_62CE339E7E3C61F9 ON formalibre__order (owner_id)
        ");
    }
}