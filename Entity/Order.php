<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

/**
 * @ORM\Table(name="formalibre__order")
 * @ORM\Entity()
 */
class Order
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
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product"
     * )
     * @ORM\JoinColumn(name="product_id", onDelete="SET NULL")
     */
    private $product;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\PriceSolution"
     * )
     * @ORM\JoinColumn(name="price_solution_id", onDelete="SET NULL")
     */
    private $priceSolution;

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

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function setPriceSolution(PriceSolution $priceSolution)
    {
        $this->priceSolution = $priceSolution;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getPriceSolution()
    {
        return $this->priceSolution;
    }
}
