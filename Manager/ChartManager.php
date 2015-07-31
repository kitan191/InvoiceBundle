<?php

namespace FormaLibre\InvoiceBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use FormaLibre\InvoiceBundle\Entity\Chart;
use FormaLibre\InvoiceBundle\Entity\Order;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\User;

/**
* @DI\Service("formalibre.manager.chart_manager")
*/
class ChartManager
{
    /**
     * @DI\InjectParams({
     *     "sharedWorkspaceManager" = @DI\Inject("formalibre.manager.shared_workspace_manager"),
     *     "om"                     = @DI\Inject("claroline.persistence.object_manager"),
     *     "configHandler"          = @DI\Inject("claroline.config.platform_config_handler")
     * })
     */
    public function __construct(
        ObjectManager $om,
        SharedWorkspaceManager $sharedWorkspaceManager,
        $configHandler
    )
    {
        $this->sharedWorkspaceManager = $sharedWorkspaceManager;
        $this->om = $om;
        $this->chartRepository = $this->om->getRepository('FormaLibre\InvoiceBundle\Entity\Chart');
        $this->configHandler = $configHandler;
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

    public function getByUser(User $user)
    {
        return $this->chartRepository->findByOwner($user);
    }

    private function getCommunication()
    {
        $x = 10; // Amount of digits
        $x--;
        $min = pow(10, $x);
        $max = pow(10, $x + 1) - 1;
        $value = rand($min, $max);
        $ctrl = $value % 97;
        if ($ctrl < 10) $ctrl = '0' . $ctrl;
        return "$value" . "$ctrl";
    }
}
