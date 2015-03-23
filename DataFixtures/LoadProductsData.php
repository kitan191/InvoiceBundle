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
                    array('duration' => 1, 'base_price' => 10)
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
                    array('duration' => 1, 'base_price' => 25)
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
                    array('duration' => 1, 'base_price' => 50)
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
                    array('duration' => 1, 'base_price' => 100)
                )
            )
        );

        foreach ($data as $info)
        {
            $product = new Product();
            $product->setCode($info['code']);
            $product->setType($info['type']);
            $product->setDetails($info['details']);

            $durations = array(1, 3, 6, 12);

            foreach ($info['pricing'] as $price) {
                foreach ($durations as $i) {
                    $priceSolution = new PriceSolution();
                    $priceSolution->setMonthDuration($price['duration'] * $i);
                    $priceSolution->setPrice($price['base_price'] * $i - 0.01);
                    $priceSolution->setProduct($product);
                    $manager->persist($priceSolution);
                    $product->addPriceSolution($priceSolution);
                }
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
