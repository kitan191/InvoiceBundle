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
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('format_structured_communication', array($this, 'format'))
        );
    }

    public function format($number)
    {
        $str = (string) $number;

        if (strlen($str) < 12) return $number;

        return '++' . substr($str, 0, 3) . '/' . substr($str, 3, 4) . '/' . substr($str, 7) . '++';
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
