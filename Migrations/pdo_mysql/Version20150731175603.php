<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/07/31 05:56:04
 */
class Version20150731175603 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__chart 
            DROP FOREIGN KEY FK_72B18A2CFD913E4D
        ");
        $this->addSql("
            DROP INDEX UNIQ_72B18A2CFD913E4D ON formalibre__chart
        ");
        $this->addSql("
            ALTER TABLE formalibre__chart 
            DROP paymentInstruction_id
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__chart 
            ADD paymentInstruction_id INT DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__chart 
            ADD CONSTRAINT FK_72B18A2CFD913E4D FOREIGN KEY (paymentInstruction_id) 
            REFERENCES payment_instructions (id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_72B18A2CFD913E4D ON formalibre__chart (paymentInstruction_id)
        ");
    }
}