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
        $configHandler->setParameter('formalibre_test_month_duration', 1);
        $configHandler->setParameter('knp_pdf_binary_path', '/usr/local/bin/wkhtmltopdf');
        $configHandler->setParameter('auto_logging_after_registration', true);
        $configHandler->setParameter('formalibre_commercial_email_support', 'changeme@email.com');
        $configHandler->setParameter('formalibre_target_platform_url', 'localhost/nico/Claroline/web/app_dev.php');
    }
}
