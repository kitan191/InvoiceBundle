<?php

namespace FormaLibre\InvoiceBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;

class LoadSupportCreditsProductsData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = array(
            array(
                'code' => 'SUPPORT_CREDITS_PACK_20',
                'type' => 'SUPPORT_CREDITS',
                'details' => array(
                    'name' => 'Pack 20',
                    'nb_credits' => 20,
                    'nb_hours' => 4,
                    'economy' => 0
                ),
                'pricing' => 250
            ),
            array(
                'code' => 'SUPPORT_CREDITS_PACK_50',
                'type' => 'SUPPORT_CREDITS',
                'details' => array(
                    'name' => 'Pack 50',
                    'nb_credits' => 50,
                    'nb_hours' => 10,
                    'economy' => 50
                ),
                'pricing' => 575
            ),
            array(
                'code' => 'SUPPORT_CREDITS_PACK_100',
                'type' => 'SUPPORT_CREDITS',
                'details' => array(
                    'name' => 'Pack 100',
                    'nb_credits' => 100,
                    'nb_hours' => 20,
                    'economy' => 250
                ),
                'pricing' => 1000
            )
        );

        foreach ($data as $info)
        {
            $product = new Product();
            $product->setCode($info['code']);
            $product->setType($info['type']);
            $product->setDetails($info['details']);
            $product->setIsActivated(true);

            $priceSolution = new PriceSolution();
            $priceSolution->setPrice($info['pricing']);
            $priceSolution->setProduct($product);
            $manager->persist($priceSolution);

            $product->addPriceSolution($priceSolution);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
