<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FormaLibre\InvoiceBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Validator("vat_validator")
 */
class VatValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value === '' || !$value) return;
        
        $vat = str_replace(' ', '', $value);
        $countryCode = substr($vat, 0, 2);
        $vat = substr($value, 2);
        $vat = str_replace('.', '', $vat);
        $vat = str_replace(' ', '', $vat);
        $data = file_get_contents("http://isvat.appspot.com/{$countryCode}/{$vat}");
        //$data = file_get_contents("http://isvat.appspot.com/BE/0599995379/");

        if ($data === 'true') {
            // its ok
        } else {
            $this->context->addViolation($constraint->error);
        }
    }
}
