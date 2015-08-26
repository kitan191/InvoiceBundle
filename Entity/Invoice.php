<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="formalibre__invoice")
 * @ORM\Entity(repositoryClass="FormaLibre\InvoiceBundle\Repository\InvoiceRepository")
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
     * @ORM\Column(type="integer", nullable=false)
     */
    private $invoiceNumber = 0;

    /**
     * @ORM\OneToOne(targetEntity="FormaLibre\InvoiceBundle\Entity\Chart", inversedBy="invoice")
     * @ORM\JoinColumn(name="chart_id", referencedColumnName="id")
     **/
    private $chart;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatRate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $vatNumber;

    /**
     * @ORM\Column(type="string")
     */
    private $paymentSystemName;

    public function setVatNumber($number)
    {
        $this->vatNumber = $number;
    }

    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    public function setChart(Chart $chart)
    {
        $this->chart = $chart;
    }

    public function getChart()
    {
        return $this->chart;
    }

    public function setVatRate($vatRate)
    {
        $this->vatRate = $vatRate;
    }

    public function getVatRate()
    {
        return $this->vatRate;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setTotalAmount($amount)
    {
        $this->totalAmount = $amount;
    }

    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    public function setVatAmount($vatAmount)
    {
        $this->vatAmount = $vatAmount;
    }

    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPaymentSystemName($paymentSystemName)
    {
        $this->paymentSystemName = $paymentSystemName;
    }

    public function getPaymentSystemName()
    {
        return $this->paymentSystemName;
    }

    public function setIsPayed($isPayed)
    {
        $this->isPayed = $isPayed;
    }

    public function isPayed()
    {
        return $this->isPayed;
    }

    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }
}
