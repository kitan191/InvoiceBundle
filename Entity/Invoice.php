<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="formalibre__invoice")
 * @ORM\Entity()
 */
class Invoice
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isPayed = false;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatRate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $vatNumber;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $countryCode;

    /**
     * @ORM\OneToOne(targetEntity="FormaLibre\InvoiceBundle\Entity\Chart", inversedBy="invoice")
     * @ORM\JoinColumn(name="chart_id", referencedColumnName="id")
     **/
    private $chart;
}
