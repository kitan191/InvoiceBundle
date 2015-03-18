<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

/**
 * @ORM\Table(name="formalibre_product")
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
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $details;

    public function __construct(
        $code,
        $price,
        array $description
    )
    {
        $this->code = $code;
        $this->price = $price;
        $this->description = $description;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setDescription(array $description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
