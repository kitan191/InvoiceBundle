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
use Claroline\CoreBundle\Entity\Facet\FieldFacet;

class LoadFacetData extends AbstractFixture implements ContainerAwareInterface
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
        $facetManager = $this->container->get('claroline.manager.facet_manager');

        $localisation = $facetManager->createFacet('Localisation', true);
        $localisationPanel = $facetManager->addPanel($localisation, 'info', true);
        $field = $facetManager->addField($localisationPanel, 'formalibre_street', FieldFacet::STRING_TYPE);
        $field = $facetManager->addField($localisationPanel, 'formalibre_cp', FieldFacet::STRING_TYPE);
        $field = $facetManager->addField($localisationPanel, 'formalibre_town', FieldFacet::STRING_TYPE);
        $field = $facetManager->addField($localisationPanel, 'formalibre_country', FieldFacet::STRING_TYPE);
        $field = $facetManager->addField($localisationPanel, 'formalibre_tel', FieldFacet::STRING_TYPE);

        $company = $facetManager->createFacet('Organisation', true);
        $companyPanel = $facetManager->addPanel($company, 'info', true);
        $field = $facetManager->addField($companyPanel, 'formalibre_company_name', FieldFacet::STRING_TYPE);
        $field = $facetManager->addField($companyPanel, 'formalibre_vat', FieldFacet::STRING_TYPE);
    }
}
