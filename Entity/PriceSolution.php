<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="formalibre__price_solution")
 * @ORM\Entity()
 */
class PriceSolution
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $monthDuration;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private $price;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product",
     *     inversedBy="priceSolutions"
     * )
     * @ORM\JoinColumn(name="product_id", onDelete="SET NULL")
     */
    private $product;

    public function getId()
    {
        return $this->id;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setMonthDuration($duration)
    {
        $this->monthDuration = $duration;
    }

    public function getMonthDuration()
    {
        return $this->monthDuration;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function __toString()
    {
        return $this->monthDuration . ' mois (' . $this->getPrice() . 'euros)';
    }
}
