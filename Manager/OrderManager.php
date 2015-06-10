<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;

/**
* @DI\Service("formalibre.manager.order_manager")
*/
class OrderManager
{
    private $om;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(
    )
    {
        $this->om = $om;
        $this->orderRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Order');
    }

    public function getPayedOrders($search, $getQuery = false)
    {
        //$this->orderRepository
    }
}
