<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FormaLibre\InvoiceBundle\Entity\Product;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;

class LoadProductsData extends AbstractFixture implements ContainerAwareInterface
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
                'code' => 'SHARED_WS_A',
                'type' => 'SHARED_WS',
                'details' => array(
                    'max_users'      => 10,
                    'max_resources' => 50,
                    'max_storage'    => '100MB'
                ),
                'pricing' => array(
                    '1' => 10,
                    '3' => 25,
                    '6' => 50,
                    '12' => 100
                )
            ),
            array(
                'code' => 'SHARED_WS_B',
                'type' => 'SHARED_WS',
                'details' => array(
                    'max_users'      => 30,
                    'max_resources' => 300,
                    'max_storage'    => '1GB'
                ),
                'pricing' => array(
                    '1' => 25,
                    '3' => 65,
                    '6' => 125,
                    '12' => 250
                )
            ),
            array(
                'code' => 'SHARED_WS_C',
                'type' => 'SHARED_WS',
                'details' => array(
                    'max_users'      => 75,
                    'max_resources' => 1000,
                    'max_storage'    => '10GB'
                ),
                'pricing' => array(
                    '1' => 50,
                    '3' => 125,
                    '6' => 250,
                    '12' => 500
                )
            ),
            array(
                'code' => 'SHARED_WS_D',
                'type' => 'SHARED_WS',
                'details' => array(
                    'max_users'      => 200,
                    'max_resources' => 10000,
                    'max_storage'    => '100GB'
                ),
                'pricing' => array(
                    '1' => 100,
                    '3' => 250,
                    '6' => 500,
                    '12' => 1000
                )
            )
        );

        foreach ($data as $info)
        {
            $product = new Product();
            $product->setCode($info['code']);
            $product->setType($info['type']);
            $product->setDetails($info['details']);

            foreach ($info['pricing'] as $duration => $price) {
                $priceSolution = new PriceSolution();
                $priceSolution->setMonthDuration($duration);
                $priceSolution->setPrice($price);
                $priceSolution->setProduct($product);
                $manager->persist($priceSolution);
                $product->addPriceSolution($priceSolution);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
