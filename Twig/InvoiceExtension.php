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

    /**
     * Get the name of the twig extention.
     *
     * @return \String
     */
    public function getName()
    {
        return 'structured_communication_extension';
    }
}
