<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\User;

/**
* @DI\Service("formalibre.manager.vat_manager")
*/
class VATManager
{
    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct($om)
    {
        $this->om = $om;
    }

    public function getVatFromOwner(User $user)
    {
        $fieldRepo = $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacet');
        $valueRepo =  $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacetValue');
        $vatField = $fieldRepo->findOneByName('formalibre_vat');
        $vatFieldValue = $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $vatField));

        return $vatFieldValue ? $vatFieldValue->getValue(): null;
    }

    public function getCountryCodeFromOwner(User $user)
    {
        $fieldRepo = $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacet');
        $valueRepo =  $this->om->getRepository('ClarolineCoreBundle:Facet\FieldFacetValue');
        $vatField = $fieldRepo->findOneByName('formalibre_country');
        $vatFieldValue = $valueRepo->findOneBy(array('user' => $user, 'fieldFacet' => $vatField));

        return $vatFieldValue ? $vatFieldValue->getValue(): null;
    }

    /**
     * is a vat number valid ?
     */
    public function isValid($value)
    {
        $vat = str_replace(' ', '', $value);
        $countryCode = substr($vat, 0, 2);
        $vat = substr($value, 2);
        $vat = str_replace('.', '', $vat);
        $vat = str_replace(' ', '', $vat);
        $data = file_get_contents("http://isvat.appspot.com/{$countryCode}/{$vat}");

        return $data === 'true' ? true: false;
    }

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
        if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') return 'BE';
        return strtoupper(file_get_contents('http://api.hostip.info/country.php?ip=' . $_SERVER['REMOTE_ADDR']));
    }
}
