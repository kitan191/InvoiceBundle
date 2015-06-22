<?php
/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\Installation\Updater;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;

class Updater050004 extends Updater
{
    private $container;
    /** @var  Connection */
    private $conn;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->conn = $container->get('doctrine.dbal.default_connection');
    }

    public function preUpdate()
    {
        $this->log('backing up the order table subjects...');
        $this->conn->query('CREATE TABLE formalibre__order_tmp
            AS (SELECT * FROM formalibre__order)');
        $this->log('truncating the previous table...');
        //ignore the foreign keys for mysql
        $this->conn->query('SET FOREIGN_KEY_CHECKS=0');
        $this->conn->query('truncate table formalibre__order');

    public function backupOrders()
    {

    }
}
