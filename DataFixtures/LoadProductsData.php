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
                    'maxUser'               => 10,
                    'maxRes'                => 50,
                    'maxSize'               => '100MB',
                    'description'           => 'Ici aussi mais moins encore',
                ),
                'pricing' => array(
                    array('duration' => 1, 'price' => 10)
                )
            ),
            array(
                'code' => 'SHARED_WS_B',
                'type' => 'SHARED_WS',
                'details' => array(
                    'maxUser'               => 30,
                    'maxRes'                => 300,
                    'maxSize'               => '1GB',
                    'description'           => 'Ici on loue des choses',
                ),
                'pricing' => array(
                    array('duration' => 1, 'price' => 25)
                )
            ),
            array(
                'code' => 'SHARED_WS_C',
                'type' => 'SHARED_WS',
                'details' => array(
                    'maxUser'               => 75,
                    'maxRes'                => 1000,
                    'maxSize'               => '10GB',
                    'description'           => 'Ici on loue un peu plus',
                ),
                'pricing' => array(
                    array('duration' => 1, 'price' => 50)
                )
            ),
            array(
                'code' => 'SHARED_WS_D',
                'type' => 'SHARED_WS',
                'details' => array(
                    'maxUser'               => 200,
                    'maxRes'                => 10000,
                    'maxSize'               => '100GB',
                    'description'           => 'Ici on loue beaucoup',
                ),
                'pricing' => array(
                    array('duration' => 1, 'price' => 100)
                )
            )
        );

        foreach ($data as $info)
        {
            $product = new Product();
            $product->setCode($info['code']);
            $product->setType($info['type']);
            $product->setDetails($info['details']);

            foreach ($info['pricing'] as $price) {
                for ($i = 1; $i < 6; $i++) {
                    $priceSolution = new PriceSolution();
                    $priceSolution->setMonthDuration($price['duration'] * $i);
                    $priceSolution->setPrice($price['price'] * $i);
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
