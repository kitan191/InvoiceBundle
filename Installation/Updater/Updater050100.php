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
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use FormaLibre\InvoiceBundle\Entity\Invoice;
use Claroline\InstallationBundle\Updater\Updater;

class Updater050100 extends Updater
{
    private $container;
    /** @var  Connection */
    private $conn;

    const MAX_BATCH_SIZE = 2;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->conn = $container->get('doctrine.dbal.default_connection');
        $this->om = $container->get('claroline.persistence.object_manager');
        $this->userRepo = $this->om->getRepository('ClarolineCoreBundle:User');
        $this->productRepo = $this->om->getRepository('FormaLibreInvoiceBundle:Product');
        $this->priceRepo = $this->om->getRepository('FormaLibreInvoiceBundle:PriceSolution');
        $this->instructRepo = $this->om->getRepository('JMS\Payment\CoreBundle\Entity\PaymentInstruction');
    }

    public function preUpdate()
    {
        /*
        $this->log('backing up the order table...');
        $this->conn->query('CREATE TABLE formalibre__order_tmp
            AS (SELECT * FROM formalibre__order)');
        $this->log('truncating the previous table...');
        //ignore the foreign keys for mysql
        $this->conn->query('SET FOREIGN_KEY_CHECKS=0');
        $this->conn->query('truncate table formalibre__order');
        */
    }

    public function postUpdate()
    {
        $orders = $this->conn->query('SELECT * FROM formalibre__order_tmp');
        $i = 0;

        foreach ($orders as $row) {
            $this->log('Restoring order for ' . $row['id'] . '...');
            $owner = $this->userRepo->find($row['owner_id']);

            $product = $row['product_id'] ? $this->productRepo->find($row['product_id']): null;
            $priceSolution = $row['price_solution_id'] ? $this->priceRepo->find($row['price_solution_id']): null;

            $paymentInstruction = $row['paymentInstruction_id'] ?
                $this->instructRepo->find($row['paymentInstruction_id']): null;

            $order = new Order();
            if ($product) $order->setProduct($product);
            if ($priceSolution) $order->setPriceSolution($priceSolution);
            $order->hasDiscount($row['hasDiscout']);

            $chart = new Chart();
            if ($paymentInstruction) $chart->setPaymentInstruction($paymentInstruction);
            $chart->setOwner($owner);

            if ($row['extendedData']) {
                $extData = $row['extendedData'];
                $obj = json_decode($extData);
                $chart->setExtendedData(get_object_vars($obj));
            }

            if ($row['validation_date']) {
                $validationDate = new \DateTime($row['validation_date']);
                $chart->setValidationDate($validationDate);
            }
            $creationDate = new \DateTime($row['creation_date']);
            $chart->setCreationDate($creationDate);
            $chart->setIpAdress($row['ipAdress']);
            $order->setChart($chart);

            $invoice = new Invoice();
            $invoice->setVatNumber($row['vatNumber']);
            $invoice->setChart($chart);
            $invoice->setVatRate($row['vatRate']);
            $invoice->setVatAmount($row['vatAmount']);
            $invoice->setAmount($row['amount']);
            $invoice->setTotalAmount($row['amount'] + $row['vatAmount']);

            if ($paymentInstruction) {
                $paymentSystemName = $paymentInstruction->getPaymentSystemName();
                $isPayed = $paymentInstruction->getState() === 4 ? true: false;
            } else {
                $paymentSystemName = 'none';
                $isPayed = false;
            }

            $invoice->setPaymentSystemName($paymentSystemName);
            $invoice->setIsPayed($isPayed);
            $invoice->setChart($chart);

            $this->om->persist($order);
            $this->om->persist($chart);
            $this->om->persist($invoice);

            $i++;

            if ($i % self::MAX_BATCH_SIZE === 0) {
                $this->om->flush();
            }
        }

        $this->om->flush();
    }
}
