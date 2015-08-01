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
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "creditSupportManager" = @DI\Inject("formalibre.manager.credit_support_manager")
     * })
     */
    public function __construct(
        ObjectManager $om,
        SharedWorkspaceManager $sharedWorkspaceManager,
        CreditSupportManager $creditSupportManager
    )
    {
        $this->om = $om;
        $this->orderRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Order');
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->creditSupportManager = $creditSupportManager;
    }

    public function complete(Order $order)
    {
         switch ($order->getProduct()->getType()) {
            case 'SHARED_WS': $this->executeWorkspaceOrder($order); break;
            case 'SUPPORT_CREDITS': $this->executeSupportCreditOrder($order); break;
        }
    }

    public function executeWorkspaceOrder(Order $order)
    {
        $sws = $this->sharedWorkspaceManager->executeOrder($order);
        $this->om->persist($sws);
        $this->om->flush();
    }
    
    public function executeSupportCreditOrder(Order $order)
    {
        $product = $order->getProduct();
        $details = $product->getDetails();
        $nbCredits = $details['nb_credits'];
        
        $this->creditSupportManager->addCreditsToUser(
            $order->getChart()->getOwner(),
            $nbCredits
        );
    }
}
