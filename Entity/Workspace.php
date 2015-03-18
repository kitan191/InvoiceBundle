<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

/**
 * @ORM\Table(name="formalibre_workspace")
 * @ORM\Entity()
 */
class Workspace
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    private $user;

    private $code;

    private $name;

    private $expDate;

    private $maxSize;

    private $maxUser;

    private $maxRes;

    private $product;

    public function getId()
    {
        return $this->id;
    }
}
