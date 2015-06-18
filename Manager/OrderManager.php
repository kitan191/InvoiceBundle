<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use FormaLibre\InvoiceBundle\Entity\Order;

/**
* @DI\Service("formalibre.manager.order_manager")
*/
class OrderManager
{
    private $om;

    /**
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager"),
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager")
     * })
     */
    public function __construct(
        ObjectManager $om,
        SharedWorkspaceManager $sharedWorkspaceManager
    )
    {
        $this->om = $om;
        $this->orderRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Order');
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
    }

    public function complete(Order $order)
    {
         switch ($order->getProduct()->getType()) {
            case 'SHARED_WS': $this->executeWorkspaceOrder($order); break;
        }
    }

    public function executeWorkspaceOrder(Order $order)
    {
        $sws = $this->sharedWorkspaceManager->executeOrder($order);
        $sws->setIsTest($isTestOrder);
        $this->om->persist($sws);
        $this->om->flush();
        $hasFreeMonth = $this->hasFreeTestMonth($order->getOwner());
    }
}
