<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;

/**
* @DI\Service("formalibre.manager.vat_manager")
*/
class VATManager
{
    public function getVAT($amt)
    {
        return $amt * $this->getVATRate($this->getClientLocation());
    }

    public function getVATRate($countryCode) {
        //check if we have a valid vat number

        switch ($countryCode) {
            case 'BE': return 0.21;
        }

        return 0.21;
    }

    public function getClientLocation()
    {
        return file_get_contents('http://api.hostip.info/country.php?ip=' . $_SERVER['REMOTE_ADDR']);
    }
}
