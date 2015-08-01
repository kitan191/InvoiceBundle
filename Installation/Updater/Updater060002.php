<?php

namespace FormaLibre\InvoiceBundle\Installation\Updater;

use Symfony\Component\DependencyInjection\ContainerInterface;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Product;
use Claroline\InstallationBundle\Updater\Updater;

class Updater060002 extends Updater
{
    private $container;
    private $om;
    private $productRepo;
    private $widgetRepo;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->om = $container->get('claroline.persistence.object_manager');
        $this->productRepo = $this->om->getRepository('FormaLibreInvoiceBundle:Product');
        $this->widgetRepo = $this->om->getRepository('ClarolineCoreBundle:Widget\Widget');
    }

    public function postUpdate()
    {
        $this->om->startFlushSuite();
        $this->createSupportCreditsProducts();
        $this->deleteFormaLibrePurchasedWidget();
        $this->om->endFlushSuite();
    }

    private function createSupportCreditsProducts()
    {
        $this->log('Creating support credits products...');

        $pack20 = $this->productRepo->findOneByCode('SUPPORT_CREDITS_PACK_20');

        if (is_null($pack20)) {
            $pack20 = new Product();
            $pack20->setCode('SUPPORT_CREDITS_PACK_20');
            $pack20->setType('SUPPORT_CREDITS');
            $details = array(
                'name' => 'Pack 20',
                'nb_credits' => 20,
                'nb_hours' => 4,
                'saving' => 0
            );
            $pack20->setDetails($details);
            $pack20->setIsActivated(true);

            $priceSolution = new PriceSolution();
            $priceSolution->setPrice(250);
            $priceSolution->setProduct($pack20);
            $this->om->persist($priceSolution);

            $pack20->addPriceSolution($priceSolution);
            $this->om->persist($pack20);
        }

        $pack50 = $this->productRepo->findOneByCode('SUPPORT_CREDITS_PACK_50');

        if (is_null($pack50)) {
            $pack50 = new Product();
            $pack50->setCode('SUPPORT_CREDITS_PACK_50');
            $pack50->setType('SUPPORT_CREDITS');
            $details = array(
                'name' => 'Pack 50',
                'nb_credits' => 50,
                'nb_hours' => 10,
                'saving' => 50
            );
            $pack50->setDetails($details);
            $pack50->setIsActivated(true);

            $priceSolution = new PriceSolution();
            $priceSolution->setPrice(575);
            $priceSolution->setProduct($pack50);
            $this->om->persist($priceSolution);

            $pack50->addPriceSolution($priceSolution);
            $this->om->persist($pack50);
        }

        $pack100 = $this->productRepo->findOneByCode('SUPPORT_CREDITS_PACK_100');

        if (is_null($pack100)) {
            $pack100 = new Product();
            $pack100->setCode('SUPPORT_CREDITS_PACK_100');
            $pack100->setType('SUPPORT_CREDITS');
            $details = array(
                'name' => 'Pack 100',
                'nb_credits' => 100,
                'nb_hours' => 20,
                'saving' => 250
            );
            $pack100->setDetails($details);
            $pack100->setIsActivated(true);

            $priceSolution = new PriceSolution();
            $priceSolution->setPrice(1000);
            $priceSolution->setProduct($pack100);
            $this->om->persist($priceSolution);

            $pack50->addPriceSolution($priceSolution);
            $this->om->persist($pack100);
        }
    }

    private function deleteFormaLibrePurchasedWidget()
    {
        $this->log('Deleting formalibre_purchased widget...');
        $widgets = $this->widgetRepo->findByName('formalibre_purchased');

        foreach ($widgets as $widget) {
            $this->om->remove($widget);
        }
    }
}
