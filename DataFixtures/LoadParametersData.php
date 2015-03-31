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

class LoadParametersData extends AbstractFixture implements ContainerAwareInterface
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
        $configHandler = $this->container->get('claroline.config.platform_config_handler');
        $configHandler->setParameter('formalibre_target_platform_url', null);
        $configHandler->setParameter('formalibre_encrypt', true);
        $configHandler->setParameter('formalibre_encryption_secret_encrypt', 'bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3'); //change me bro
        $configHandler->setParameter('jms_payment_core_secret', 1234);
        $configHandler->setParameter('jms_payment_paypal_username', 'nicolas.godfraind-facilitator_api1.gmail.com');
        $configHandler->setParameter('jms_payment_paypal_password', 'G58R5XS3EP4FKM9D');
        $configHandler->setParameter('jms_payment_paypal_signature', 'Aq4JdqtzEOjJCsXDzrYABanKDensADzpi82xR4w65Q.RwVPCL-6hr.vV');
        $configHandler->setParameter('jms_payment_paypal_debug', true);
    }
}
