<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use Claroline\CoreBundle\Persistence\ObjectManager;

/**
* @DI\Service("formalibre.manager.chart_manager")
*/
class ChartManager
{
    /**
     * @DI\InjectParams({
     *     "invoiceManager"         = @DI\Inject("formalibre.manager.invoice_manager"),
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "om"                     = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(
        InvoiceManager $invoiceManager,
        ObjectManager $om,
        SharedWorkspaceManager $sharedWorkspaceManager
    )
    {
        $this->invoiceManager = $invoiceManager;
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->om = $om;
        $this->chartRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Chart');
    }

    public function getChart($chartId)
    {
        return $chartId === 0 ? new Chart(): $this->chartRepository->find($chartId);
    }

    public function addOrder(Order $order, Chart $chart = null)
    {
        if (!$chart) $chart = new Chart();
        $order->setChart($chart);
    }

    public function validate(Chart $chart)
    {
        $orders = $chart->getOrders();

        foreach ($order as $order) {
            switch ($order->getProduct()->getType()) {
                case 'SHARED_WS': $this->completeSharedWorkspaceOrder($order); break;
            }
        }
    }

    public function completeSharedWorkspaceOrder(Order $order)
    {
        $duration = $order->hasDiscount() ?
            $order->getPriceSolution()->getMonthDuration() + $this->container->get('claroline.config.platform_config_handler')->getParameter('formalibre_test_month_duration'):
            $order->getPriceSolution()->getMonthDuration();

        $this->sharedWorkspaceManager->executeWorkspaceOrder(
            $order,
            $duration,
            $order->getSharedWorkspace()
        );
    }
}
