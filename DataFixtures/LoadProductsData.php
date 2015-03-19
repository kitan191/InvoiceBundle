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
                'code' => 'SHARED_WS_A_1',
                'type' => 'SHARED_WS',
                'description' => array(
                    'maxUser'               => 10,
                    'maxRes'                => 50,
                    'maxSize'               => '100MB',
                    'price'                 => 10,
                    'description'           => 'Ici on loue 1 moi pour 10 euros',
                    'duration'              => 1
                )
            ),
            array(
                'code' => 'SHARED_WS_B_1',
                'type' => 'SHARED_WS',
                'description' => array(
                    'maxUser'               => 30,
                    'maxRes'                => 300,
                    'maxSize'               => '1GB',
                    'price'                 => 25,
                    'description'           => 'Ici on loue 1 moi pour 10 euros',
                    'duration' => 1
                )
            ),
            array(
                'code' => 'SHARED_WS_C_1',
                'type' => 'SHARED_WS',
                'description' => array(
                    'maxUser'               => 75,
                    'maxRes'                => 1000,
                    'maxSize'               => '10GB',
                    'price'                 => 50,
                    'description'           => 'Ici on loue 1 moi pour 10 euros',
                    'subscribtion_duration' => 1
                )
            ),
            array(
                'code' => 'SHARED_WS_D_1',
                'type' => 'SHARED_WS',
                'description' => array(
                    'maxUser'               => 200,
                    'maxRes'                => 10000,
                    'maxSize'               => '100GB',
                    'price'                 => 100,
                    'description'           => 'Ici on loue 1 moi pour 10 euros',
                    'duration' => 1
                )
            )
        );
        
        foreach ($data as $info)
        {
            $product = new Product();
            $product->setCode($info['code']);
            $product->setType($info['type']);
            $product->setDescription($info['description']);
            $manager->persist($product);
        }
        
        $manager->flush();
    }
}
