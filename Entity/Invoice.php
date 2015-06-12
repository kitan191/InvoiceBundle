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
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $invoiceNumber;

    private $paymentMethod;

    /**
     * @ORM\OneToOne(targetEntity="FormaLibre\InvoiceBundle\Entity\Chart", inversedBy="invoice")
     * @ORM\JoinColumn(name="chart_id", referencedColumnName="id")
     **/
    private $chart;

    public function setChart(Chart $chart)
    {
        $this->chart = $chart;
    }

    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function getChart()
    {
        return $this->chart;
    }
}
