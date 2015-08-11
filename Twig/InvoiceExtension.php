<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\Twig;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use Claroline\CoreBundle\Entity\User;

/**
 * @DI\Service
 * @DI\Tag("twig.extension")
 */
class InvoiceExtension extends \Twig_Extension
{
    private $container;

    /**
     * @DI\InjectParams({
     *     "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('format_structured_communication', array($this, 'formatCommunication')),
            new \Twig_SimpleFilter('format_price', array($this, 'formatPrice'))
        );
    }

    /*
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_facet_value', array($this, 'getFieldValue')),
            new \Twig_SimpleFunction('get_invoice_locale', array($this, 'getInvoiceLocale')),
            new \Twig_SimpleFunction('get_chart_net_total', array($this, 'getChartNetTotal')),
            new \Twig_SimpleFunction('get_chart_vat_rate', array($this, 'getChartVatRate')),
            new \Twig_SimpleFunction('has_free_workspace_month', array($this, 'hasFreeWorkspaceMonth')),
        );
    }

    public function formatCommunication($number)
    {
        $str = (string) $number;

        if (strlen($str) < 12) return $number;

        return '++' . substr($str, 0, 3) . '/' . substr($str, 3, 4) . '/' . substr($str, 7) . '++';
    }

    public function formatPrice($number)
    {
        $locale = $this->container->get('request')->getLocale();

        if (strtolower($locale) === 'en') {
            return number_format($number, 2, '.', ',');
        }

        return number_format($number, 2, ',', '.');
    }

    public function getFieldValue(User $user, $fieldName)
    {
        $facetManager = $this->container->get('claroline.manager.facet_manager');
        $ffvs = $facetManager->getFieldValuesByUser($user);

        foreach ($ffvs as $ffv) {
            if ($ffv->getFieldFacet()->getName() === $fieldName) {
                return $facetManager->getDisplayedValue($ffv);
            }
        }

        return null;
    }

    public function getInvoiceLocale(User $user)
    {
        $invoiceManager = $this->container->get('formalibre.manager.invoice_manager');

        return $invoiceManager->getInvoiceLocale($user);
    }

    public function getChartNetTotal(Chart $chart)
    {
        $netTotal = 0;

        foreach ($chart->getOrders() as $order) {
            $netTotal += $order->getPriceSolution()->getPrice() * $order->getQuantity();
        }

        return $netTotal;
    }

    public function getChartVatRate(Chart $chart)
    {
        $vatManager = $this->container->get('formalibre.manager.vat_manager');
        $user = $chart->getOwner();
        $vatRate = $vatManager->getVatFromOwner($user) ?
            0: $vatManager->getVATRate($vatManager->getCountryCodeFromOwner($user));

        return $vatRate;
    }

    public function hasFreeWorkspaceMonth(User $user)
    {
        return $this->container->get('formalibre.manager.shared_workspace_manager')->hasFreeTestMonth($user);
    }

    /**
     * Get the name of the twig extention.
     *
     * @return \String
     */
    public function getName()
    {
        return 'invoice_extension';
    }
}
