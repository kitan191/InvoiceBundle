<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\PriceSolution;
use FormaLibre\InvoiceBundle\Entity\Product;

/**
 * @DI\Service("formalibre.manager.price_solution_manager")
 */
class PriceSolutionManager
{
    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct($om)
    {
        $this->om = $om;
    }

    public function create(Product $product, $price, $duration = null)
    {
        $ps = new PriceSolution();
        $ps->setPrice($price);
        $ps->setProduct($product);
        $ps->setMonthDuration($duration);
        $this->om->persist($ps);
        $this->om->flush();

        return $ps;
    }

    public function remove(PriceSolution $ps)
    {
        $this->om->remove($ps);
        $this->om->flush();
    }
}
