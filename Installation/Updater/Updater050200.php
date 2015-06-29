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

class Updater050200 extends Updater
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->om = $container->get('claroline.persistence.object_manager');
        $this->productRepo = $this->om->getRepository('FormaLibreInvoiceBundle:Product');
    }

    public function postUpdate()
    {
        $products = $this->productRepo->findAll();
        $this->log('Activate products...');

        foreach ($products as $product) {
            $product->setIsActivated(true);
            $this->om->persist($product);
        }

        $this->om->flush();
    }
}
