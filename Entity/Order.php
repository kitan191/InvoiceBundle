<?php

namespace FormaLibre\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="formalibre__order")
 * @ORM\Entity(repositoryClass="FormaLibre\InvoiceBundle\Repository\OrderRepository")
 */
class Order
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product"
     * )
     * @ORM\JoinColumn(name="product_id", onDelete="SET NULL")
     */
    private $product;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Chart"
     * )
     * @ORM\JoinColumn(name="chart_id", onDelete="SET NULL")
     */
    private $chart;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\PriceSolution"
     * )
     * @ORM\JoinColumn(name="price_solution_id", onDelete="SET NULL")
     */
    private $priceSolution;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $hasDiscount = false;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="FormaLibre\InvoiceBundle\Entity\Product\SharedWorkspace",
     *     inversedBy="order"
     * )
     * @ORM\JoinColumn(name="shared_workspace_id", onDelete="SET NULL")
     */
    private $sharedWorkspace;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $quantity = 1;

    public function getId()
    {
        return $this->id;
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getPriceSolution()
    {
        return $this->priceSolution;
    }

    public function setPriceSolution($priceSolution)
    {
        $this->priceSolution = $priceSolution;
    }

    public function setHasDiscount($bool)
    {
        $this->hasDiscount = $bool;
    }

    public function hasDiscount()
    {
        return $this->hasDiscount;
    }

    public function setChart(Chart $chart)
    {
        $this->chart = $chart;
    }

    public function getChart()
    {
        return $this->chart;
    }

    public function setSharedWorkspace(SharedWorkspace $sws)
    {
        $this->setSharedWorkspace = $sws;
    }

    public function getSharedWorkspace()
    {
        return $this->sharedWorkspace;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }
}
