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
        //https://github.com/modmore/euvatrates.com
        $json = file_get_contents('https://euvatrates.com/rates.json');
        $data = json_decode($json);
        $rates = $data->rates;

        if (property_exists($rates, $countryCode)) return $rates->$countryCode->standard_rate / 100;

        return 0.21;
    }

    public function getClientLocation()
    {
        return strtoupper(file_get_contents('http://api.hostip.info/country.php?ip=' . $_SERVER['REMOTE_ADDR']));
    }
}
