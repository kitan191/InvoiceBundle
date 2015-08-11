<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="formalibre__product")
 * @ORM\Entity()
 */
class Product
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $details;

    /**
     * @ORM\OneToMany(
     *     mappedBy="product",
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\PriceSolution",
     *     cascade={"persist"}
     * )
     */
    private $priceSolutions;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isActivated = false;

    public function __construct()
    {
        $this->priceSolutions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function addPriceSolution(PriceSolution $price)
    {
        $this->priceSolutions->add($price);
    }

    public function getPriceSolutions()
    {
        return $this->priceSolutions;
    }

    public function setIsActivated($isActivated)
    {
        $this->isActivated = $isActivated;
    }

    public function isActivated()
    {
        return $this->isActivated;
    }

    public function getName()
    {
        if (isset($this->details['pretty_name'])) return $this->details['pretty_name'];
        if (isset($this->details['name'])) return $this->details['name'];
        
        return $this->code;
    }
}
