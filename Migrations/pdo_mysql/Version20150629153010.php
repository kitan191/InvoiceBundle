<?php

namespace FormaLibre\InvoiceBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/06/29 03:30:11
 */
class Version20150629153010 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__product 
            ADD isActivated TINYINT(1) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__price_solution CHANGE monthDuration monthDuration INT DEFAULT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE formalibre__price_solution CHANGE monthDuration monthDuration INT NOT NULL
        ");
        $this->addSql("
            ALTER TABLE formalibre__product 
            DROP isActivated
        ");
    }
}