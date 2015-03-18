<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

/**
 * @ORM\Table(name="formalibre_order_workspace")
 * @ORM\Entity()
 */
class WorkspaceOrder
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\OneToOne(targetEntity="JMS\Payment\CoreBundle\Entity\PaymentInstruction") */
    private $paymentInstruction;


    public function getId()
    {
        return $this->id;
    }

    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    public function setPaymentInstruction(PaymentInstruction $instruction)
    {
        $this->paymentInstruction = $instruction;
    }

    // ...
}
