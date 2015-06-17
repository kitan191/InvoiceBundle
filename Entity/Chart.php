<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Claroline\CoreBundle\Entity\User;

/**
 * @ORM\Table(name="formalibre__chart")
 * @ORM\Entity()
 */
class Chart
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\OneToOne(targetEntity="JMS\Payment\CoreBundle\Entity\PaymentInstruction") */
    private $paymentInstruction;

    /**
     * @ORM\OneToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Invoice",
     *     mappedBy="chart"
     * )
     */

    private $invoice;
    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $owner;

    /**
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    /**
     * @ORM\Column(name="validation_date", type="datetime", nullable=true)
     */
    protected $validationDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $ipAddress;

     /**
     * @ORM\OneToMany(targetEntity="FormaLibre\InvoiceBundle\Entity\Order", mappedBy="chart")
     **/
    private $orders;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $extendedData;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

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

    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setIpAddress($ip)
    {
        $this->ipAddress = $ip;
    }

    public function getIpadress()
    {
        return $this->ipAdress;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function setValidationDate($date)
    {
        $this->validationDate = $date;
    }

    public function getValidationDate()
    {
        return $this->validationDate;
    }

    public function getOrders()
    {
        return $this->orders;
    }

    public function setExtendedData(array $data)
    {
        $this->extendedData = $data;
    }

    public function getExtendedData()
    {
        return $this->extendedData;
    }

    public function setIpAdress($ipAdress)
    {
        $this->ipAdress = $ipAdress;
    }
}
